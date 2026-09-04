<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\CostumeItem;
use App\Models\Group;

class CostumeController extends Controller
{
    // parāda visus tērpa vienības, kas piešķirtas konkrētam lietotājam 
    public function index(Group $group)
    {
        abort_unless(auth()->user()->inGroup($group), 403);

        $items = auth()->user()
            ->assignedCostumeItems() // make sure this relationship exists in User model
            ->with('costume')
            ->whereHas('costume', fn($query) => $query->where('group_id', $group->id))
            ->get();

        return view('member.index', compact('items', 'group'));
    }

    // parāda visus tērpa vienības, kas piešķirtas konkrētam lietotājam
    public function assigned()
    {
        $items = CostumeItem::where('assigned_to', auth()->id())
            ->with('costume')
            ->get();

        return view('member.assigned', compact('items'));
    }

    // noņem tērpa vienību no lietotāja
    public function unassign(CostumeItem $item)
    {
        $this->authorize('unassignAsMember', $item);

        $item->update([
            'assigned_to' => null,
            'assigned_at' => null,
        ]);

        return back()->with('success', 'Costume unassigned.');
    }
}

