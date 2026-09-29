<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Exceptions\CostumeItemUnavailableException;
use App\Mail\MemberAddedMail;
use App\Mail\MemberRemovedMail;
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
        $sets = auth()->user()->adminGroups()->first()?->costumeSets ?? collect();

        return view('admin.members.create', compact('sets'));
    }

    // pieņem vienu vai vairākus studentus vienā reizē – katru rindu apstrādā neatkarīgi,
    // lai viena rindas kļūda nebloķē pārējo veiksmīgo pievienošanu
    public function store(Request $request)
    {
        $adminGroup = auth()->user()->adminGroups()->first();
        abort_if(is_null($adminGroup), 403, 'You do not have a group yet.');

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

        $created = 0;
        $attached = 0;
        $skipped = [];
        $needsManualPassword = [];

        foreach ($validated['members'] as $row) {
            // ja šāds konts jau pastāv, pievieno to grupai, nevis veido jaunu
            $existing = User::withTrashed()->where('email', $row['email'])->first();

            $result = $existing
                ? $this->attachExisting($existing, $adminGroup, $setId)
                : $this->createAndInvite($row, $adminGroup, $setId);

            if ($result['status'] === 'created') {
                $created++;
            } elseif ($result['status'] === 'created_email_failed') {
                $created++;
                $needsManualPassword[] = "{$result['name']} ({$result['email']}): {$result['password']}";
            } elseif ($result['status'] === 'attached') {
                $attached++;
            } else {
                $skipped[] = $result['message'];
            }
        }

        return redirect()->route('admin.members.index')
            ->with($this->summaryFlash($created, $attached, $skipped, $needsManualPassword));
    }

    // apkopo visu rindu iznākumus vienā, salasāmā paziņojumā
    private function summaryFlash(int $created, int $attached, array $skipped, array $needsManualPassword): array
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

        if ($needsManualPassword) {
            $message .= "\nCouldn't email these — give the temporary password in person:\n".implode("\n", $needsManualPassword);
        }

        $level = ($created + $attached === 0) ? 'error' : (($skipped || $needsManualPassword) ? 'warning' : 'success');

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

        if (! $user->hasRole('member')) {
            $user->assignRole('member');
        }

        $group->members()->attach($user->id, ['costume_set_id' => $setId]);

        rescue(fn () => Mail::to($user->email)->send(new MemberAddedMail($user, $group->name)));

        return ['status' => 'attached', 'name' => $user->name, 'email' => $user->email];
    }

    // izveido jaunu kontu ar pagaidu paroli un nosūta uzaicinājuma e-pastu
    // konts tiek izveidots neatkarīgi no tā, vai e-pasts izdodas nosūtīt – e-pasta kļūme nekad nedrīkst bloķēt dalībnieka pievienošanu
    private function createAndInvite(array $validated, Group $group, ?int $setId = null): array
    {
        $tempPassword = Str::random(12);

        $member = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($tempPassword),
            'must_change_password' => true, // pagaidu parole der tikai pirmajai pieslēgšanās reizei
        ]);

        $member->assignRole('member');
        $group->members()->attach($member->id, ['costume_set_id' => $setId]);

        if ($this->sendInvite($member, $group->name, $tempPassword)) {
            return ['status' => 'created', 'name' => $member->name, 'email' => $member->email];
        }

        return ['status' => 'created_email_failed', 'name' => $member->name, 'email' => $member->email, 'password' => $tempPassword];
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

    // maina komplektu vienam vai vairākiem studentiem (Members saraksts un studenta lapa)
    public function updateSet(Request $request)
    {
        $adminGroup = auth()->user()->adminGroups()->first();
        abort_if(is_null($adminGroup), 403, 'You do not have a group yet.');

        $validated = $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'integer',
            'costume_set_id' => 'nullable|integer|exists:costume_sets,id,group_id,'.$adminGroup->id,
        ], [
            'user_ids.required' => 'Tick at least one student first.',
        ]);

        // tikai šīs grupas dalībnieki – svešus id klusi ignorējam
        $ids = $adminGroup->members()->whereIn('users.id', $validated['user_ids'])->pluck('users.id');

        foreach ($ids as $id) {
            $adminGroup->members()->updateExistingPivot($id, ['costume_set_id' => $validated['costume_set_id'] ?? null]);
        }

        $setName = isset($validated['costume_set_id'])
            ? $adminGroup->costumeSets()->whereKey($validated['costume_set_id'])->value('name')
            : null;

        $message = $setName
            ? $ids->count().' '.Str::plural('student', $ids->count())." moved to “{$setName}”."
            : $ids->count().' '.Str::plural('student', $ids->count()).' now have no set.';

        return back()->with('success', $message);
    }

    // skolotājs izsniedz studentam konkrētu vienību, kas viņam ir rokās – izvēlas to pēc koda uz birkas (piem. VAI-03),
    // lai sistēmā piešķirtā vienība vienmēr sakrīt ar fiziski iedoto
    public function handOut(Request $request, User $user)
    {
        $this->authorize('view', $user);

        $adminGroup = auth()->user()->adminGroups()->first();
        abort_if(is_null($adminGroup), 403, 'You do not have a group yet.');

        $validated = $request->validate([
            'item_id' => ['required', 'integer'],
        ]);

        // tikai šīs grupas tērpu vienības
        $item = $adminGroup->costumeItems()->whereKey($validated['item_id'])->first();
        abort_if(is_null($item), 422, 'That item is not in your group.');

        try {
            $item->assignTo($user, auth()->user());
        } catch (CostumeItemUnavailableException) {
            return back()->with('error', "Item {$item->code} is already with someone else. Check the code on the label.");
        }

        return back()->with('success', "{$item->code} ({$item->costume->name}) handed out to {$user->name}.");
    }

    // skolotājs jau iedeva paroli citādi – noņem brīdinājumu par nepiegādāto uzaicinājumu
    public function dismissInvite(User $member)
    {
        $this->authorize('view', $member);

        $member->update(['invite_email_failed_at' => null]);

        return back()->with('success', "Invite warning for {$member->name} dismissed.");
    }

    // parāda visus lietotājus
    public function index()
    {
        $adminGroup = auth()->user()->adminGroups()->first();
        // roles un citu grupu skaits jau šeit, lai skats var izvēlēties "Remove" (atsaista) vai "Delete" (dzēš kontu) pogu
        $members = $adminGroup
            ? $adminGroup->members()->with('roles')->withCount('memberGroups')->get()
            : collect();

        // nesen izņemti dalībnieki, kuri jebkad bijuši šajā grupā – ne tikai tie, kam
        // deactivated_with_group_id sakrīt (students var būt deaktivizēts citas grupas dēļ)
        $trashedMembers = $adminGroup
            ? User::onlyTrashed()
                ->whereHas('memberGroups', fn ($query) => $query->whereKey($adminGroup->id))
                ->get()
            : collect();

        $sets = $adminGroup?->costumeSets ?? collect();

        return view('admin.members.index', compact('members', 'trashedMembers', 'sets'));
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

        // komplekts skolotāja grupā (students var būt vairākās grupās, bet skolotājam ir viena)
        $adminGroup = auth()->user()->adminGroups()->first();
        $sets = $adminGroup?->costumeSets ?? collect();
        $currentSetId = $adminGroup?->members()->whereKey($user->id)->first()?->pivot->costume_set_id;

        // tērpi ar brīvajām vienībām izsniegšanas formai – skolotājs izvēlas konkrēto kodu
        $costumes = $adminGroup
            ? $adminGroup->costumes()->with(['items' => fn ($q) => $q->whereNull('assigned_to')->orderBy('code')])->orderBy('name')->get()
            : collect();

        return view('admin.members.show', compact('user', 'sets', 'currentSetId', 'costumes'));
    }

    public function destroy(User $user)
    {
        $this->authorize('delete', $user);

        $adminGroup = auth()->user()->adminGroups()->first();
        abort_if(is_null($adminGroup), 403);

        $name = $user->name;

        // skolotāju kontus nekad nedzēšam, un students var būt arī citās grupās -> abos gadījumos tikai
        // atsaistam NO ŠĪS grupas, konts un pārējās grupas/tiesības paliek neskartas
        if ($user->hasRole('admin') || $user->memberGroups()->count() > 1) {
            $heldFromThisGroup = $user->assignedCostumeItems()
                ->whereHas('costume', fn ($query) => $query->where('group_id', $adminGroup->id))
                ->get();

            foreach ($heldFromThisGroup as $item) {
                $item->release(auth()->user(), 'left_group');
            }

            $adminGroup->members()->detach($user->id);

            // students uzzina par izņemšanu (tāpat kā par pievienošanu); e-pasta kļūme darbību neaptur
            rescue(fn () => Mail::to($user->email)->send(new MemberRemovedMail($user, $adminGroup->name)));

            $reason = $user->hasRole('admin') ? "they're a teacher" : "they're still in other groups";

            return redirect()->route('admin.members.index')
                ->with('success', "“{$name}” removed from your group. Since {$reason}, their account was kept.");
        }

        // vienīgā grupa un nav skolotājs -> konta mīkstā dzēšana (atbrīvo VISAS vienības un aizver atvērtos vēstures ierakstus)
        // datubāzes ārējā atslēga arī iztīra assigned_to, bet vēstures ieraksts citādi paliktu "vēl neatdots"
        foreach ($user->assignedCostumeItems as $item) {
            $item->release(auth()->user(), 'removed');
        }

        // skolotāja veikta dzēšana tagad ir atgriezeniska, tāpat kā grupas dzēšana –
        // atzīmē, kuras grupas dēļ konts deaktivizēts, lai to varētu vēlāk atjaunot
        $user->update(['deactivated_with_group_id' => $adminGroup->id]);
        $user->delete();

        $purgeDate = now()->addDays(Group::PURGE_AFTER_DAYS)->format('d.m.Y');

        rescue(fn () => Mail::to($user->email)->send(new MemberRemovedMail($user, $adminGroup->name, $purgeDate)));

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
