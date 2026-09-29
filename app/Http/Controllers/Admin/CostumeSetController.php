<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CostumeSet;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

// tērpu komplektu pārvaldība grupas iestatījumos (piem. "Meitenes", "Puiši")
class CostumeSetController extends Controller
{
    public function store(Request $request)
    {
        $group = auth()->user()->adminGroups()->first();
        abort_if(is_null($group), 403, 'You do not have a group yet.');

        $validated = $request->validateWithBag('costumeSet', [
            'set_name' => ['required', 'string', 'max:60', Rule::unique('costume_sets', 'name')->where('group_id', $group->id)],
        ]);

        $group->costumeSets()->create(['name' => $validated['set_name']]);

        return redirect()->route('admin.group.settings')->with('success', "Set “{$validated['set_name']}” added.");
    }

    public function update(Request $request, CostumeSet $costumeSet)
    {
        $this->authorizeSet($costumeSet);

        $validated = $request->validateWithBag('costumeSet'.$costumeSet->id, [
            'set_name' => ['required', 'string', 'max:60', Rule::unique('costume_sets', 'name')->where('group_id', $costumeSet->group_id)->ignore($costumeSet->id)],
        ]);

        $costumeSet->update(['name' => $validated['set_name']]);

        return redirect()->route('admin.group.settings')->with('success', "Set renamed to “{$costumeSet->name}”.");
    }

    // dzēšot komplektu, tā tērpi kļūst kopīgi un studentiem komplekts tiek noņemts (nullOnDelete)
    public function destroy(CostumeSet $costumeSet)
    {
        $this->authorizeSet($costumeSet);

        $name = $costumeSet->name;
        $costumeSet->delete();

        return redirect()->route('admin.group.settings')
            ->with('success', "Set “{$name}” deleted. Its costumes are now shared, and its students have no set.");
    }

    // komplektu drīkst mainīt tikai tās grupas skolotājs, kurai tas pieder
    private function authorizeSet(CostumeSet $costumeSet): void
    {
        abort_unless(
            auth()->user()->adminGroups()->whereKey($costumeSet->group_id)->exists(),
            403
        );
    }
}
