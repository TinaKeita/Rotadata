<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\GroupDeletionMail;
use App\Models\Event;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

use function Illuminate\Support\defer;

class GroupController extends Controller
{
    // grupas iestatījumu lapa – aktīva grupa vai nesen dzēsta grupa ar atjaunošanas iespēju
    public function edit()
    {
        $group = $this->activeGroup();
        $trashedGroup = $this->trashedGroup();

        if (is_null($group) && is_null($trashedGroup)) {
            return redirect()->route('dashboard')->with('error', "You don't have a group yet. Create one from your profile.");
        }

        $stats = $group ? $this->impact($group) : null;

        // vēl neatbildēts pieprasījums nodot grupu citam skolotājam
        $pendingTransfer = $group?->transfers()->open()->with('toUser')->latest()->first();

        return view('admin.group.settings', compact('group', 'trashedGroup', 'stats', 'pendingTransfer'));
    }

    // skolotājs izveido vēl vienu grupu (no profila) – tā kļūst par pašreizējo
    public function store(Request $request)
    {
        $validated = $request->validateWithBag('newGroup', [
            'group_name' => 'required|string|max:255',
        ]);

        $group = Group::create(['name' => $validated['group_name'], 'admin_id' => auth()->id()]);
        auth()->user()->switchToGroup($group);

        return redirect()->route('admin.group.settings')
            ->with('success', "Group “{$group->name}” created. It's now the group you're working in.");
    }

    // pārslēdzas uz citu savu grupu un atver tās iestatījumus (navigācijas grupu saites)
    public function open(Group $group)
    {
        if (! auth()->user()->ownsGroup($group)) {
            return redirect()->route('dashboard')->with('error', "That group isn't yours.");
        }

        auth()->user()->switchToGroup($group);

        return redirect()->route('admin.group.settings');
    }

    // pārsauc grupu
    public function update(Request $request)
    {
        $group = $this->activeGroup();
        abort_if(is_null($group), 404, "You don't have a group to do that with.");
        $this->authorize('update', $group);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $group->update(['name' => $validated['name']]);

        return redirect()->route('admin.group.settings')
            ->with('success', "Group renamed to “{$group->name}”.");
    }

    // dzēšanas apstiprinājuma lapa – jāievada grupas nosaukums un parole
    public function confirm()
    {
        $group = $this->activeGroup();
        abort_if(is_null($group), 404, "You don't have a group to do that with.");
        $this->authorize('delete', $group);

        $stats = $this->impact($group);

        return view('admin.group.delete', compact('group', 'stats'));
    }

    // mīksti dzēš grupu un deaktivizē tikai-šīs-grupas dalībniekus
    public function destroy(Request $request)
    {
        $group = $this->activeGroup();
        abort_if(is_null($group), 404, "You don't have a group to do that with.");
        $this->authorize('delete', $group);

        $request->validate([
            'name' => ['required', Rule::in([$group->name])],
            'password' => ['required', 'current_password'],
        ], [
            'name.in' => 'The group name does not match.',
        ]);

        $stats = $this->impact($group);

        // izsniegtie tērpi vispirms jāsaņem atpakaļ – citādi zūd pēdas, kam kas ir rokās
        if ($stats['items_out'] > 0) {
            throw ValidationException::withMessages([
                'name' => "{$stats['items_out']} item(s) are still checked out. Take them back or have students return them before deleting the group.",
            ]);
        }

        $groupName = $group->name;
        $purgeDate = now()->addDays(Group::PURGE_AFTER_DAYS)->format('d.m.Y');

        // notikušie koncerti saglabā savus dalībniekus, pirms viņi tiek deaktivizēti
        Event::snapshotFinished();

        // pieprasījuma atcelšana, dalībnieku deaktivizēšana un grupas dzēšana notiek kopā vai nenotiek nemaz
        $result = DB::transaction(function () use ($group) {
            // izdzēstu grupu vairs nevar nodot – neatbildētais pieprasījums tiek atcelts
            $group->transfers()->where('status', 'pending')->update(['status' => 'cancelled', 'responded_at' => now()]);

            return $group->softDeleteWithMembers();
        });

        // e-pastus sūta pēc atbildes atgriešanas, lai skolotāja klikšķis ir tūlītējs
        defer(function () use ($result, $groupName, $purgeDate) {
            foreach ($result['deactivated'] as $member) {
                rescue(fn () => Mail::to($member->email)->send(
                    new GroupDeletionMail($member, $groupName, GroupDeletionMail::DEACTIVATED, $purgeDate)
                ));
            }

            foreach ($result['removed'] as $member) {
                rescue(fn () => Mail::to($member->email)->send(
                    new GroupDeletionMail($member, $groupName, GroupDeletionMail::REMOVED)
                ));
            }
        });

        return redirect()->route('admin.group.settings')
            ->with('success', "Group “{$groupName}” deleted. Students are being notified. You can restore it until {$purgeDate}.");
    }

    // atjauno nesen dzēstu grupu un ar to deaktivizētos dalībniekus
    public function restore(Request $request)
    {
        $group = $this->trashedGroup();
        abort_if(is_null($group), 404, "You don't have a group to do that with.");
        $this->authorize('restore', $group);

        $groupName = $group->name;

        // koncerti, kas notika, kamēr dalībnieki bija deaktivizēti, viņus savā skaitā neieskaita
        Event::snapshotFinished();

        // grupa un tās dalībnieki tiek atjaunoti kopā vai nemaz
        $reactivated = DB::transaction(fn () => $group->restoreWithMembers());

        defer(function () use ($reactivated, $groupName) {
            foreach ($reactivated as $member) {
                rescue(fn () => Mail::to($member->email)->send(
                    new GroupDeletionMail($member, $groupName, GroupDeletionMail::RESTORED)
                ));
            }
        });

        return redirect()->route('admin.group.settings')
            ->with('success', "Group “{$groupName}” restored.");
    }

    // iztīra grupu uzreiz, negaidot 30 dienas – prasa paroli, jo tas ir neatgriezeniski
    public function forceDestroy(Request $request)
    {
        $group = $this->trashedGroup();
        abort_if(is_null($group), 404, "You don't have a group to do that with.");
        $this->authorize('forceDelete', $group);

        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $groupName = $group->name;
        $group->purge();

        return redirect()->route('dashboard')
            ->with('success', "Group “{$groupName}” permanently deleted.");
    }

    private function activeGroup(): ?Group
    {
        return auth()->user()->currentGroup();
    }

    private function trashedGroup(): ?Group
    {
        return auth()->user()->adminGroups()->onlyTrashed()->latest('deleted_at')->first();
    }

    // ko dzēšana ietekmēs – rāda skolotājam pirms apstiprināšanas
    private function impact(Group $group): array
    {
        $members = $group->members()->get();
        $students = $members->reject(fn ($member) => $member->hasRole('admin'));

        $sole = $students->filter(fn ($member) => $member->memberGroups()->count() === 1);

        return [
            'costumes' => $group->costumes()->count(),
            'items' => $group->costumeItems()->count(),
            'items_out' => $group->costumeItems()->whereNotNull('assigned_to')->count(),
            'students' => $students->count(),
            'students_deactivated' => $sole->count(),
            'students_kept' => $students->count() - $sole->count(),
        ];
    }
}
