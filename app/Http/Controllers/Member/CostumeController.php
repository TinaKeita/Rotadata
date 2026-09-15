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
        abort_unless(auth()->user()->inGroup($group), 403);

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
            ->with('item.costume')
            ->get();

        return view('member.index', compact('items', 'group', 'history'));
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
        abort_unless(auth()->user()->inGroup($group), 403);

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

