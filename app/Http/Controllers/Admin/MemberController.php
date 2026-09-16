<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\MemberAddedMail;
use App\Mail\MemberWelcomeMail;
use App\Mail\PasswordResetByTeacherMail;
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class MemberController extends Controller
{
    // forma jauna lietotāja pievienošanai
    public function create()
    {
        return view('admin.members.create');
    }

    public function store(Request $request)
    {
        $adminGroup = auth()->user()->adminGroups()->first();
        abort_if(is_null($adminGroup), 403, 'You do not have a group yet.');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
        ]);

        // ja šāds konts jau pastāv, pievieno to grupai, nevis veido jaunu
        $existing = User::withTrashed()->where('email', $validated['email'])->first();

        return $existing
            ? $this->attachExisting($existing, $adminGroup)
            : $this->createAndInvite($validated, $adminGroup);
    }

    // pievieno esošu kontu skolotāja grupai un paziņo par to e-pastā (bez paroles)
    private function attachExisting(User $user, Group $group)
    {
        if ($user->trashed()) {
            return back()->withInput()->with('error',
                'That email belongs to an account that was deactivated when its group was deleted. It can’t be added right now.');
        }

        if ($user->hasRole('admin')) {
            return back()->withInput()->with('error', 'That email belongs to a teacher account.');
        }

        if ($group->members()->whereKey($user->id)->exists()) {
            return redirect()->route('admin.members.index')
                ->with('warning', "“{$user->name}” is already in this group.");
        }

        if (! $user->hasRole('member')) {
            $user->assignRole('member');
        }

        $group->members()->attach($user->id);

        rescue(fn () => Mail::to($user->email)->send(new MemberAddedMail($user, $group->name)));

        return redirect()->route('admin.members.index')
            ->with('success', "“{$user->name}” was added to your group and notified by email.");
    }

    // izveido jaunu kontu ar pagaidu paroli un nosūta uzaicinājuma e-pastu
    // konts tiek izveidots neatkarīgi no tā, vai e-pasts izdodas nosūtīt – e-pasta kļūme nekad nedrīkst bloķēt dalībnieka pievienošanu
    private function createAndInvite(array $validated, Group $group)
    {
        $tempPassword = Str::random(12);

        $member = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($tempPassword),
            'must_change_password' => true, // pagaidu parole der tikai pirmajai pieslēgšanās reizei
        ]);

        $member->assignRole('member');
        $group->members()->attach($member->id);

        if ($this->sendInvite($member, $group->name, $tempPassword)) {
            return redirect()->route('admin.members.index')
                ->with('success', "Member “{$member->name}” created and an invitation was sent to {$member->email}.");
        }

        return redirect()->route('admin.members.index')
            ->with('warning', "Member “{$member->name}” created, but the email could not be sent. Temporary password: {$tempPassword} — give it to the member in person, or resend the invite from their profile once the problem is fixed.");
    }

    // vēlreiz nosūta uzaicinājumu ar jaunu pagaidu paroli – tikai kamēr dalībnieks vēl nav pats pieslēdzies
    public function resendInvite(User $member)
    {
        $this->authorize('view', $member);

        abort_unless($member->must_change_password, 403,
            'This member has already signed in and set their own password — an invite can no longer be resent.');

        $tempPassword = Str::random(12);
        $member->update(['password' => Hash::make($tempPassword), 'must_change_password' => true]);

        $groupName = auth()->user()->adminGroups()
            ->whereHas('members', fn ($query) => $query->whereKey($member->id))
            ->value('name');

        if ($this->sendInvite($member, $groupName, $tempPassword)) {
            return back()->with('success', "Invite resent to {$member->email}.");
        }

        return back()->with('warning', "Could not send the email. New temporary password: {$tempPassword} — give it to the member in person.");
    }

    // students aizmirsis paroli – skolotājs var to atiestatīt, bet tikai apstiprinot ar SAVU paroli
    // (aizsardzība pret nejaušu klikšķi vai svešu piekļuvi neaizslēgtai sesijai)
    public function resetPassword(Request $request, User $member)
    {
        $this->authorize('view', $member);

        $request->validateWithBag('resetPassword', [
            'password' => ['required', 'current_password'],
        ]);

        $tempPassword = Str::random(12);
        $member->update([
            'password' => Hash::make($tempPassword),
            'must_change_password' => true, // vecā parole vairs nederēs, jaunā ir tikai vienreizējai pieslēgšanās reizei
        ]);

        $groupName = auth()->user()->adminGroups()
            ->whereHas('members', fn ($query) => $query->whereKey($member->id))
            ->value('name');

        try {
            Mail::to($member->email)->send(new PasswordResetByTeacherMail($member, $tempPassword, $groupName));

            return redirect()->route('admin.members.show', $member)
                ->with('success', "{$member->name}’s password was reset. They’ll get a new temporary password by email.");
        } catch (\Throwable $e) {
            \Log::error('Failed to send teacher-initiated password reset email: '.$e->getMessage(), [
                'email' => $member->email,
                'member_id' => $member->id,
            ]);

            return redirect()->route('admin.members.show', $member)
                ->with('warning', "Password reset, but the email could not be sent. New temporary password: {$tempPassword} — give it to {$member->name} in person.");
        }
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

    // parāda visus lietotājus
    public function index()
    {
        $adminGroup = auth()->user()->adminGroups()->first();
        $members = $adminGroup ? $adminGroup->members : collect();

        // nesen izņemti dalībnieki (vienīgā grupa), kurus vēl var atjaunot
        $trashedMembers = $adminGroup
            ? User::onlyTrashed()->where('deactivated_with_group_id', $adminGroup->id)->get()
            : collect();

        return view('admin.members.index', compact('members', 'trashedMembers'));
    }

    public function show(User $user)
    {
        $this->authorize('view', $user);

        // students var būt vairākās grupās – rāda tikai izsniegumus no ŠĪ skolotāja grupas(-ām),
        // nevis visu vēsturi, kurā ietilptu arī cita skolotāja grupas tērpi
        $groupIds = auth()->user()->adminGroups()->pluck('id');

        $user->load([
            'costumeAssignments' => function ($query) use ($groupIds) {
                $query->whereHas('item.costume', fn ($q) => $q->whereIn('group_id', $groupIds))
                    ->with(['item.costume', 'returnedBy']);
            },
        ]);

        return view('admin.members.show', compact('user'));
    }

    public function destroy(User $user)
    {
        $this->authorize('delete', $user);

        $adminGroup = auth()->user()->adminGroups()->first();
        abort_if(is_null($adminGroup), 403);

        $name = $user->name;

        // students ir arī citās grupās -> izņem tikai NO ŠĪS grupas, konts un pārējās grupas paliek neskartas
        if ($user->memberGroups()->count() > 1) {
            $heldFromThisGroup = $user->assignedCostumeItems()
                ->whereHas('costume', fn ($query) => $query->where('group_id', $adminGroup->id))
                ->get();

            foreach ($heldFromThisGroup as $item) {
                $item->release(auth()->user(), 'left_group');
            }

            $adminGroup->members()->detach($user->id);

            return redirect()->route('admin.members.index')
                ->with('success', "“{$name}” removed from your group. They're still in other groups, so their account was kept.");
        }

        // vienīgā grupa -> konta mīkstā dzēšana (atbrīvo VISAS vienības un aizver atvērtos vēstures ierakstus)
        // datubāzes ārējā atslēga arī iztīra assigned_to, bet vēstures ieraksts citādi paliktu "vēl neatdots"
        foreach ($user->assignedCostumeItems as $item) {
            $item->release(auth()->user(), 'removed');
        }

        // skolotāja veikta dzēšana tagad ir atgriezeniska, tāpat kā grupas dzēšana –
        // atzīmē, kuras grupas dēļ konts deaktivizēts, lai to varētu vēlāk atjaunot
        $user->update(['deactivated_with_group_id' => $adminGroup->id]);
        $user->delete();

        $purgeDate = now()->addDays(Group::PURGE_AFTER_DAYS)->format('d.m.Y');

        return redirect()->route('admin.members.index')
            ->with('success', "Member “{$name}” deleted. Their costumes have been released. You can restore the account until {$purgeDate}.");
    }

    // atjauno skolotāja izņemtu (vienīgās grupas) dalībnieku
    public function restore(User $user)
    {
        abort_unless($user->trashed(), 404);
        $this->authorize('restore', $user);

        $name = $user->name;

        $user->restore();
        $user->update(['deactivated_with_group_id' => null]);

        return redirect()->route('admin.members.index')
            ->with('success', "“{$name}” restored and added back to your group.");
    }

    // iztīra izņemto dalībnieku uzreiz, negaidot 30 dienas – prasa paroli, jo tas ir neatgriezeniski
    public function forceDestroy(Request $request, User $user)
    {
        abort_unless($user->trashed(), 404);
        $this->authorize('forceDelete', $user);

        $request->validateWithBag('forceDestroy'.$user->id, [
            'password' => ['required', 'current_password'],
        ]);

        $name = $user->name;
        $user->forceDelete();

        return redirect()->route('admin.members.index')
            ->with('success', "“{$name}” permanently deleted.");
    }

}
