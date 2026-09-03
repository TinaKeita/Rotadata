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
        $request->validate([
            'name' => 'required|string|max:255',
            'quantity' => 'required|integer|min:1',
        ]);

        $costume = Costume::create([
            'name' => $request->name,
            'quantity' => $request->quantity,
            'image' => null,
            'group_id' => auth()->user()->adminGroups()->first()->id,
        ]);

        // izveido atsevišķas tērpa vienības
        for ($i = 0; $i < $request->quantity; $i++) {
            $costume->items()->create([
                'qr_code' => Str::uuid(), // unikāls qr kods priekš katras vienības
                'assigned_to' => null,    
            ]);
        }

        return redirect()->route('admin.costumes.index')->with('success', 'Costume created successfully!');
    }

    public function show(Costume $costume)
    {
        $items = $costume->items()->with('user')->get();

        return view('admin.costumes.show', compact('costume', 'items'));
    }

    public function destroy(Costume $costume)
    {
        $costume->delete();

        return redirect()->route('admin.costumes.index')->with('success', 'Costume deleted.');
    }

    public function unassign(CostumeItem $item)
    {
        $item->update([
            'assigned_to' => null,
            'assigned_at' => null,
        ]);

        return back()->with('success', 'Item unassigned successfully.');
    }
}
