<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\GroupInvitationMail;
use App\Mail\MemberWelcomeMail;
use App\Models\Event;
use App\Models\Group;
use App\Models\GroupInvitation;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

// studentu uzaicināšana grupai (jauni konti ar pagaidu paroli vai uzaicinājums esošam kontam) un uzaicinājumu atkārtota sūtīšana
class MemberInviteController extends Controller
{
    // cik uzaicinājumu (jaunu kontu vai uzaicinājumu esošiem kontiem – katrs nozīmē e-pastu kādam citam) viens skolotājs
    // drīkst nosūtīt 24 stundās – pietiek visai deju kopai uzreiz, bet reģistrācija ir publiska, tāpēc aplikāciju
    // nevar izmantot surogātpasta sūtīšanai
    public const INVITES_PER_DAY = 60;

    // forma jauna lietotāja pievienošanai
    public function create()
    {
        $sets = auth()->user()->currentGroup()?->costumeSets ?? collect();

        return view('admin.members.create', compact('sets'));
    }

    // pieņem vienu vai vairākus studentus vienā reizē – katru rindu apstrādā neatkarīgi,
    // lai viena rindas kļūda nebloķē pārējo veiksmīgo pievienošanu
    public function store(Request $request)
    {
        $adminGroup = auth()->user()->currentGroup();
        abort_if(is_null($adminGroup), 403, 'You do not have a group yet.');

        // e-pastu salīdzina bez lielo/mazo burtu atšķirības – "Marta@Example.COM" ir tas pats konts, kas "marta@example.com"
        $request->merge(['members' => collect((array) $request->input('members', []))
            ->map(fn ($row) => is_array($row) && isset($row['email']) ? [...$row, 'email' => Str::lower(trim((string) $row['email']))] : $row)
            ->all()]);

        // pielāgo kļūdu ziņojumus, lai tajos būtu rindas numurs ("Row 2 email"), nevis "members.1.email"
        $attributes = [];
        foreach ((array) $request->input('members', []) as $i => $row) {
            $attributes["members.$i.name"] = 'row '.($i + 1).' name';
            $attributes["members.$i.email"] = 'row '.($i + 1).' email';
        }

        $validated = $request->validate([
            'members' => 'required|array|min:1|max:50',
            'members.*.name' => 'required|string|max:255',
            'members.*.email' => 'required|email|max:255|distinct',
            // viens komplekts visai partijai – skolotājs parasti pievieno visas meitenes, tad visus puišus
            'costume_set_id' => 'nullable|integer|exists:costume_sets,id,group_id,'.$adminGroup->id,
        ], [], $attributes);

        $setId = $validated['costume_set_id'] ?? null;

        // jauns dalībnieks pēc koncerta tajā nepiedalījās – notikušie koncerti tiek nofiksēti pirms pievienošanas
        Event::snapshotFinished();

        $created = 0;
        $invited = 0;
        $skipped = [];
        $emailFailed = 0;
        $limitKey = 'member-invites:'.auth()->id();

        foreach ($validated['members'] as $row) {
            if (RateLimiter::tooManyAttempts($limitKey, self::INVITES_PER_DAY)) {
                $skipped[] = "“{$row['email']}” — you've sent ".self::INVITES_PER_DAY.' invites today, the daily limit. Try again tomorrow.';

                continue;
            }

            // ja šāds konts jau pastāv, tam nosūta uzaicinājumu, nevis veido jaunu kontu
            $existing = User::withTrashed()->whereRaw('lower(email) = ?', [$row['email']])->first();

            $result = $existing
                ? $this->inviteExisting($existing, $adminGroup, $setId)
                : $this->createAndInvite($row, $adminGroup, $setId);

            if (in_array($result['status'], ['created', 'created_email_failed', 'invited'], true)) {
                RateLimiter::hit($limitKey, 60 * 60 * 24);
            }

            if ($result['status'] === 'created') {
                $created++;
            } elseif ($result['status'] === 'created_email_failed') {
                $created++;
                $emailFailed++;
            } elseif ($result['status'] === 'invited') {
                $invited++;
            } else {
                $skipped[] = $result['message'];
            }
        }

        return redirect()->route('admin.members.index')
            ->with($this->summaryFlash($created, $invited, $skipped, $emailFailed));
    }

    // apkopo visu rindu iznākumus vienā, salasāmā paziņojumā
    private function summaryFlash(int $created, int $invited, array $skipped, int $emailFailed): array
    {
        $parts = [];
        if ($created > 0) {
            $parts[] = "Added {$created} new ".Str::plural('account', $created).' — they join once they sign in and set their own password.';
        }
        if ($invited > 0) {
            $parts[] = "Invited {$invited} existing ".Str::plural('account', $invited).' — they join once they accept.';
        }

        $message = $parts ? implode(' ', $parts) : 'No members were added.';

        if ($skipped) {
            $message .= "\nSkipped: ".implode('; ', $skipped);
        }

        if ($emailFailed > 0) {
            $message .= "\nCouldn't email {$emailFailed} ".Str::plural('student', $emailFailed).' — use “Resend invite” on their profile once email works.';
        }

        $level = ($created + $invited === 0) ? 'error' : (($skipped || $emailFailed) ? 'warning' : 'success');

        return [$level => $message];
    }

    // esošu kontu grupai uzreiz nepievieno – nosūta uzaicinājumu, ko lietotājs pats pieņem vai noraida.
    // Kamēr tas nav pieņemts, skolotājs kontu neredz un tērpus izsniegt nevar
    private function inviteExisting(User $user, Group $group, ?int $setId = null): array
    {
        if ($user->trashed()) {
            return ['status' => 'skipped', 'message' => "“{$user->email}” belongs to a deactivated account and can’t be added right now."];
        }

        // skolotājs drīkst būt arī cita skolotāja grupas dalībnieks – bet ne savas pašas grupas
        if ($user->ownsGroup($group)) {
            return ['status' => 'skipped', 'message' => "“{$user->email}” is your own account."];
        }

        if ($group->members()->whereKey($user->id)->exists()) {
            return ['status' => 'skipped', 'message' => "“{$user->email}” is already in this group."];
        }

        if ($group->invitations()->open()->where('user_id', $user->id)->exists()) {
            return ['status' => 'skipped', 'message' => "“{$user->email}” has already been invited and hasn't answered yet."];
        }

        // viens ieraksts katram cilvēkam katrā grupā – iepriekš noraidīts vai beidzies uzaicinājums tiek atjaunots
        $invitation = GroupInvitation::updateOrCreate(
            ['group_id' => $group->id, 'user_id' => $user->id],
            [
                'invited_by' => auth()->id(),
                'costume_set_id' => $setId,
                'status' => 'pending',
                'expires_at' => now()->addDays(GroupInvitation::EXPIRES_AFTER_DAYS),
                'responded_at' => null,
            ],
        );

        // students, kurš vēl nav izvēlējies savu paroli, citus e-pastus kā pirmo uzaicinājumu nesaņem –
        // šo uzaicinājumu viņš ieraudzīs savā sākumlapā pēc pieslēgšanās
        if (! $user->must_change_password) {
            rescue(fn () => Mail::to($user->email)->send(new GroupInvitationMail($user, $invitation->load(['group', 'inviter']))));
        }

        return ['status' => 'invited', 'name' => $user->name, 'email' => $user->email];
    }

    // skolotājs atsauc vēl neatbildētu uzaicinājumu
    public function cancelInvitation(GroupInvitation $invitation)
    {
        abort_unless($invitation->group && auth()->user()->ownsGroup($invitation->group), 403);

        GroupInvitation::whereKey($invitation->id)->where('status', 'pending')
            ->update(['status' => 'cancelled', 'responded_at' => now()]);

        return back()->with('success', "Invitation for {$invitation->user->name} cancelled.");
    }

    // izveido jaunu kontu ar pagaidu paroli un nosūta uzaicinājuma e-pastu
    // konts tiek izveidots neatkarīgi no tā, vai e-pasts izdodas nosūtīt – e-pasta kļūme nekad nedrīkst bloķēt dalībnieka pievienošanu
    private function createAndInvite(array $validated, Group $group, ?int $setId = null): array
    {
        $tempPassword = Str::random(12);

        // konts, loma un dalība top kopā; e-pastu sūta tikai pēc tam, kad viss ir saglabāts.
        // Ja cits skolotājs šo e-pastu izveidoja mirkli agrāk, transakcija tiek atcelta un esošais konts tiek pievienots
        try {
            $member = $this->createMember($validated, $group, $setId, $tempPassword);
        } catch (UniqueConstraintViolationException) {
            $existing = User::withTrashed()->whereRaw('lower(email) = ?', [$validated['email']])->first();

            return $existing
                ? $this->inviteExisting($existing, $group, $setId)
                : ['status' => 'skipped', 'message' => "“{$validated['email']}” could not be added — please try again."];
        }

        if ($this->sendInvite($member, $group->name, $tempPassword)) {
            return ['status' => 'created', 'name' => $member->name, 'email' => $member->email];
        }

        // skolotājs pagaidu paroli neredz – students to saņem tikai e-pastā vai vēlāk ar "Resend invite" saiti
        return ['status' => 'created_email_failed', 'name' => $member->name, 'email' => $member->email];
    }

    // jauns konts ar lomu un dalību grupā – vienā transakcijā
    private function createMember(array $validated, Group $group, ?int $setId, string $tempPassword): User
    {
        return DB::transaction(function () use ($validated, $group, $setId, $tempPassword) {
            $member = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($tempPassword),
                'must_change_password' => true, // pēc pirmās pieslēgšanās jāizvēlas sava parole
                'temporary_password_expires_at' => now()->addDays(User::TEMPORARY_PASSWORD_DAYS),
            ]);

            $member->assignRole('member');
            $group->members()->attach($member->id, ['costume_set_id' => $setId]);

            return $member;
        });
    }

    // uzaicinājums nav pienācis – nosūta saiti, ar kuru dalībnieks pats izvēlas paroli.
    // Skolotājs paroli neredz un nemaina, tāpēc šādi nevar pārņemt kāda cita kontu
    public function resendInvite(User $member)
    {
        $this->authorize('view', $member);

        abort_unless($member->must_change_password, 403,
            'This member has already signed in and set their own password — an invite can no longer be resent.');

        try {
            $status = Password::sendResetLink(['email' => $member->email]);
        } catch (\Throwable $e) {
            \Log::error('Failed to send invite link: '.$e->getMessage(), ['member_id' => $member->id]);
            $member->update(['invite_email_failed_at' => now()]);

            return back()->with('warning', "Could not send the email to {$member->email}. Check the address and try again later.");
        }

        if ($status === Password::RESET_THROTTLED) {
            return back()->with('warning', 'A link was sent to this member a moment ago. Wait a minute before sending another.');
        }

        $member->update(['invite_email_failed_at' => null]);

        return back()->with('success', "Sent {$member->email} a link to set their password.");
    }

    // mēģina nosūtīt uzaicinājuma e-pastu; atzīmē kontu, ja neizdodas, lai skolotājs to redz un var mēģināt vēlreiz
    private function sendInvite(User $member, ?string $groupName, string $tempPassword): bool
    {
        try {
            Mail::to($member->email)->send(new MemberWelcomeMail($member, $tempPassword, $groupName));
            $member->update(['invite_email_failed_at' => null]);

            return true;
        } catch (\Throwable $e) {
            \Log::error('Failed to send member invitation email: '.$e->getMessage(), [
                'email' => $member->email,
                'member_id' => $member->id,
            ]);
            $member->update(['invite_email_failed_at' => now()]);

            return false;
        }
    }

    // skolotājs noņem brīdinājumu par nepiegādāto uzaicinājumu (piem. students jau saņēmis saiti citā veidā)
    public function dismissInvite(User $member)
    {
        $this->authorize('view', $member);

        $member->update(['invite_email_failed_at' => null]);

        return back()->with('success', "Invite warning for {$member->name} dismissed.");
    }
}
