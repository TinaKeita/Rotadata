<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\GroupDeletionMail;
use App\Models\Group;
use Illuminate\Http\Request;
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

        abort_if(is_null($group) && is_null($trashedGroup), 404);

        $stats = $group ? $this->impact($group) : null;

        return view('admin.group.settings', compact('group', 'trashedGroup', 'stats'));
    }

    // pārsauc grupu
    public function update(Request $request)
    {
        $group = $this->activeGroup();
        abort_if(is_null($group), 404);
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
        abort_if(is_null($group), 404);
        $this->authorize('delete', $group);

        $stats = $this->impact($group);

        return view('admin.group.delete', compact('group', 'stats'));
    }

    // mīksti dzēš grupu un deaktivizē tikai-šīs-grupas dalībniekus
    public function destroy(Request $request)
    {
        $group = $this->activeGroup();
        abort_if(is_null($group), 404);
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

        $result = $group->softDeleteWithMembers();

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
        abort_if(is_null($group), 404);
        $this->authorize('restore', $group);

        $groupName = $group->name;
        $reactivated = $group->restoreWithMembers();

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
        abort_if(is_null($group), 404);
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
        return auth()->user()->adminGroups()->first();
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
