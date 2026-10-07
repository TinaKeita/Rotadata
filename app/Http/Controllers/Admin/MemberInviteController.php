<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\MemberAddedMail;
use App\Mail\MemberWelcomeMail;
use App\Models\Event;
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

// studentu pievienošana grupai (jauni konti vai esoši) un uzaicinājumu atkārtota sūtīšana
class MemberInviteController extends Controller
{
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
        $attached = 0;
        $skipped = [];
        $emailFailed = 0;

        foreach ($validated['members'] as $row) {
            // ja šāds konts jau pastāv, pievieno to grupai, nevis veido jaunu
            $existing = User::withTrashed()->whereRaw('lower(email) = ?', [$row['email']])->first();

            $result = $existing
                ? $this->attachExisting($existing, $adminGroup, $setId)
                : $this->createAndInvite($row, $adminGroup, $setId);

            if ($result['status'] === 'created') {
                $created++;
            } elseif ($result['status'] === 'created_email_failed') {
                $created++;
                $emailFailed++;
            } elseif ($result['status'] === 'attached') {
                $attached++;
            } else {
                $skipped[] = $result['message'];
            }
        }

        return redirect()->route('admin.members.index')
            ->with($this->summaryFlash($created, $attached, $skipped, $emailFailed));
    }

    // apkopo visu rindu iznākumus vienā, salasāmā paziņojumā
    private function summaryFlash(int $created, int $attached, array $skipped, int $emailFailed): array
    {
        $parts = [];
        if ($created > 0) {
            $parts[] = "{$created} new ".Str::plural('account', $created).' created and invited';
        }
        if ($attached > 0) {
            $parts[] = "{$attached} existing ".Str::plural('account', $attached).' added';
        }

        $message = $parts ? ('Added '.implode(', ', $parts).'.') : 'No members were added.';

        if ($skipped) {
            $message .= "\nSkipped: ".implode('; ', $skipped);
        }

        if ($emailFailed > 0) {
            $message .= "\nCouldn't email {$emailFailed} ".Str::plural('student', $emailFailed).' — use “Resend invite” on their profile once email works.';
        }

        $level = ($created + $attached === 0) ? 'error' : (($skipped || $emailFailed) ? 'warning' : 'success');

        return [$level => $message];
    }

    // pievieno esošu kontu skolotāja grupai un paziņo par to e-pastā (bez paroles)
    private function attachExisting(User $user, Group $group, ?int $setId = null): array
    {
        if ($user->trashed()) {
            return ['status' => 'skipped', 'message' => "“{$user->email}” belongs to a deactivated account and can’t be added right now."];
        }

        // skolotājs drīkst būt arī cita skolotāja grupas dalībnieks – bet ne savas pašas grupas
        if ($user->ownsGroup($group)) {
            return ['status' => 'skipped', 'message' => "“{$user->email}” is your own account."];
        }

        if ($group->members()->whereKey($user->id)->exists()) {
            return ['status' => 'skipped', 'message' => "“{$user->name}” is already in this group."];
        }

        DB::transaction(function () use ($user, $group, $setId) {
            if (! $user->hasRole('member')) {
                $user->assignRole('member');
            }

            $group->members()->attach($user->id, ['costume_set_id' => $setId]);
        });

        rescue(fn () => Mail::to($user->email)->send(new MemberAddedMail($user, $group->name)));

        return ['status' => 'attached', 'name' => $user->name, 'email' => $user->email];
    }

    // izveido jaunu kontu ar pagaidu paroli un nosūta uzaicinājuma e-pastu
    // konts tiek izveidots neatkarīgi no tā, vai e-pasts izdodas nosūtīt – e-pasta kļūme nekad nedrīkst bloķēt dalībnieka pievienošanu
    private function createAndInvite(array $validated, Group $group, ?int $setId = null): array
    {
        $tempPassword = Str::random(12);

        // konts, loma un dalība top kopā; e-pastu sūta tikai pēc tam, kad viss ir saglabāts
        $member = DB::transaction(function () use ($validated, $group, $setId, $tempPassword) {
            $member = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($tempPassword),
                'must_change_password' => true, // pagaidu parole der tikai pirmajai pieslēgšanās reizei
            ]);

            $member->assignRole('member');
            $group->members()->attach($member->id, ['costume_set_id' => $setId]);

            return $member;
        });

        if ($this->sendInvite($member, $group->name, $tempPassword)) {
            return ['status' => 'created', 'name' => $member->name, 'email' => $member->email];
        }

        // skolotājs pagaidu paroli neredz – students to saņem tikai e-pastā vai vēlāk ar "Resend invite" saiti
        return ['status' => 'created_email_failed', 'name' => $member->name, 'email' => $member->email];
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
