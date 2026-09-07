<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Costume;
use App\Models\CostumeItem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CostumeController extends Controller
{
    public function index()
    {
        $group = auth()->user()->adminGroups()->first();
        $costumes = $group ? $group->costumes : collect();

        return view('admin.costumes.index', compact('costumes'));
    }

    // forma jauna tērpa pievienošanai
    public function create()
    {
        return view('admin.costumes.create');
    }

    public function store(Request $request)
    {
        $group = auth()->user()->adminGroups()->first();
        abort_if(is_null($group), 403, 'You do not have a group yet.');

        $request->validate([
            'name' => 'required|string|max:255',
            'quantity' => 'required|integer|min:1',
        ]);

        $prefix = Costume::makeCodePrefix($request->name, $group->id);

        $costume = Costume::create([
            'name' => $request->name,
            'code_prefix' => $prefix,
            'quantity' => $request->quantity,
            'image' => null,
            'group_id' => $group->id,
        ]);

        // izveido atsevišķas tērpa vienības ar QR kodu un salasāmu kodu
        for ($i = 1; $i <= $request->quantity; $i++) {
            $costume->items()->create([
                'qr_code' => Str::uuid(), // unikāls qr kods priekš katras vienības
                'code' => sprintf('%s-%02d', $prefix, $i), // piem. BRU-01
                'assigned_to' => null,
            ]);
        }

        return redirect()->route('admin.costumes.index')->with('success', 'Costume created successfully!');
    }

    public function show(Costume $costume)
    {
        $this->authorize('view', $costume);

        $items = $costume->items()->with('user')->get();

        return view('admin.costumes.show', compact('costume', 'items'));
    }

    public function destroy(Costume $costume)
    {
        $this->authorize('delete', $costume);

        $costume->delete();

        return redirect()->route('admin.costumes.index')->with('success', 'Costume deleted.');
    }

    public function unassign(CostumeItem $item)
    {
        $this->authorize('unassignAsAdmin', $item);

        $item->update([
            'assigned_to' => null,
            'assigned_at' => null,
        ]);

        return back()->with('success', 'Item unassigned successfully.');
    }
}
