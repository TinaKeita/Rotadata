<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\CostumeItem;
use App\Models\Group;

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
}

