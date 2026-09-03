<?php

namespace App\Http\Controllers;

use App\Models\CostumeItem;
use Illuminate\Http\Request;

class ScanController extends Controller
{
    public function show($code)
    {
        $item = CostumeItem::where('qr_code', $code)->firstOrFail();

        if ($item->assigned_to) {
            return view('scan.assigned');
        }

        $members = $item->costume->group->members;

        return view('scan.assign', compact('item', 'members'));
    }

    public function assign(Request $request, $code)
    {
        $item = CostumeItem::where('qr_code', $code)->firstOrFail();

        if ($item->assigned_to) {
            return back()->with('error', 'Already assigned.');
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $item->update([
            'assigned_to' => $request->user_id,
            'assigned_at' => now(),
        ]);

        return view('scan.success');
    }
}
