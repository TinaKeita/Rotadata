<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CostumeSet;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

// tērpu komplektu pārvaldība grupas iestatījumos (piem. "Meitenes", "Puiši")
class CostumeSetController extends Controller
{
    public function store(Request $request)
    {
        $group = auth()->user()->currentGroup();
        abort_if(is_null($group), 403, 'You do not have a group yet.');

        $validated = $request->validateWithBag('costumeSet', [
            'set_name' => ['required', 'string', 'max:60', Rule::unique('costume_sets', 'name')->where('group_id', $group->id)],
        ]);

        $group->costumeSets()->create(['name' => $validated['set_name']]);

        return redirect()->route('admin.group.settings')->with('success', "Set “{$validated['set_name']}” added.");
    }

    public function update(Request $request, CostumeSet $costumeSet)
    {
        $this->authorize('update', $costumeSet);

        $validated = $request->validateWithBag('costumeSet'.$costumeSet->id, [
            'set_name' => ['required', 'string', 'max:60', Rule::unique('costume_sets', 'name')->where('group_id', $costumeSet->group_id)->ignore($costumeSet->id)],
        ]);

        $costumeSet->update(['name' => $validated['set_name']]);

        return redirect()->route('admin.group.settings')->with('success', "Set renamed to “{$costumeSet->name}”.");
    }

    // dzēšot komplektu, tā tērpi kļūst kopīgi un studentiem komplekts tiek noņemts (nullOnDelete)
    public function destroy(CostumeSet $costumeSet)
    {
        $this->authorize('delete', $costumeSet);

        $name = $costumeSet->name;

        // notikušie koncerti saglabā toreizējos komplektus, pirms tie tiek noņemti
        Event::snapshotFinished();

        $costumeSet->delete();

        return redirect()->route('admin.group.settings')
            ->with('success', "Set “{$name}” deleted. Its costumes are now shared, and its students have no set.");
    }
}
