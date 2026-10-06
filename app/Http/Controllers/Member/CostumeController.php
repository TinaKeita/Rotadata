<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\CostumeItem;
use App\Models\Group;
use App\Notifications\StudentLeftGroupNotification;

class CostumeController extends Controller
{
    // parāda dalībniekam piešķirtās tērpu vienības konkrētajā grupā
    public function index(Group $group)
    {
        $this->authorize('viewAsMember', $group);

        $items = auth()->user()
            ->assignedCostumeItems()
            ->with('costume')
            ->whereHas('costume', fn($query) => $query->where('group_id', $group->id))
            ->get();

        // pagātnes tērpi šajā grupā – jau atdotie
        $history = auth()->user()
            ->costumeAssignments()
            ->whereNotNull('returned_at')
            ->whereHas('item.costume', fn($query) => $query->where('group_id', $group->id))
            ->with(['item.costume', 'returnedBy'])
            ->get();

        // studenta komplekts šajā grupā (nosaka, kuri koncerta tērpi viņam vajadzīgi)
        $setId = auth()->user()->memberGroups()->whereKey($group->id)->first()?->pivot->costume_set_id;
        $setName = $setId ? \App\Models\CostumeSet::whereKey($setId)->value('name') : null;

        return view('member.index', compact('items', 'group', 'history', 'setName'));
    }

    // noņem tērpa vienību no lietotāja
    public function unassign(CostumeItem $item)
    {
        $this->authorize('unassignAsMember', $item);

        $code = $item->code;

        $item->release(auth()->user(), 'self');

        return back()->with('success', "Item {$code} returned.");
    }

    // students pats pamet grupu – konts vienmēr paliek, tikai piederība šai grupai izzūd
    public function leave(Group $group)
    {
        $this->authorize('leave', $group);

        $itemsHeld = auth()->user()
            ->assignedCostumeItems()
            ->whereHas('costume', fn ($query) => $query->where('group_id', $group->id))
            ->count();

        if ($itemsHeld > 0) {
            return back()->with('error',
                "You still have {$itemsHeld} item(s) checked out from this group. Return them before leaving.");
        }

        $group->members()->detach(auth()->id());

        // skolotājs to redz kā paziņojumu nākamajā pieslēgšanās reizē
        $group->admin?->notify(new StudentLeftGroupNotification(auth()->user()->name, $group->name));

        return redirect()->route('dashboard')->with('success', "You've left {$group->name}.");
    }
}

