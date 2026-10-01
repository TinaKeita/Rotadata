<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Exceptions\CostumeItemUnavailableException;
use App\Models\Costume;
use App\Models\CostumeItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CostumeController extends Controller
{
    public function index()
    {
        $group = auth()->user()->currentGroup();
        $costumes = $group
            ? $group->costumes()->with('costumeSet')->withCount(['items', 'items as items_out_count' => fn ($q) => $q->whereNotNull('assigned_to')])->get()
            : collect();

        return view('admin.costumes.index', compact('costumes'));
    }

    // forma jauna tērpa pievienošanai
    public function create()
    {
        $sets = auth()->user()->currentGroup()?->costumeSets ?? collect();

        return view('admin.costumes.create', compact('sets'));
    }

    public function store(Request $request)
    {
        $group = auth()->user()->currentGroup();
        abort_if(is_null($group), 403, 'You do not have a group yet.');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'quantity' => 'required|integer|min:1|max:200',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'costume_set_id' => 'nullable|integer|exists:costume_sets,id,group_id,'.$group->id,
        ]);

        $costume = Costume::create([
            'name' => $validated['name'],
            'costume_set_id' => $validated['costume_set_id'] ?? null,
            'quantity' => 0,
            'image' => $request->hasFile('image') ? $request->file('image')->store('costumes', 'public') : null,
            'group_id' => $group->id,
        ]);

        $costume->addItems((int) $validated['quantity']);

        return redirect()->route('admin.costumes.index')->with('success', "Costume “{$costume->name}” created with {$validated['quantity']} items.");
    }

    // forma tērpa nosaukuma rediģēšanai
    public function edit(Costume $costume)
    {
        $this->authorize('update', $costume);

        $sets = $costume->group->costumeSets;

        return view('admin.costumes.edit', compact('costume', 'sets'));
    }

    public function update(Request $request, Costume $costume)
    {
        $this->authorize('update', $costume);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'remove_image' => 'nullable|boolean',
            'costume_set_id' => 'nullable|integer|exists:costume_sets,id,group_id,'.$costume->group_id,
        ]);

        // vecais foto jāizdzēš gan aizstājot, gan noņemot, lai nekrāj nelietotus failus krātuvē
        if ($request->hasFile('image')) {
            if ($costume->image) {
                Storage::disk('public')->delete($costume->image);
            }
            $costume->image = $request->file('image')->store('costumes', 'public');
        } elseif ($request->boolean('remove_image') && $costume->image) {
            Storage::disk('public')->delete($costume->image);
            $costume->image = null;
        }

        $costume->name = $validated['name'];
        $costume->costume_set_id = $validated['costume_set_id'] ?? null;
        $costume->save();

        return redirect()->route('admin.costumes.show', $costume)
            ->with('success', "Costume “{$costume->name}” saved.");
    }

    // pievieno tērpam papildu vienības
    public function addItems(Request $request, Costume $costume)
    {
        $this->authorize('update', $costume);

        $validated = $request->validate([
            'count' => 'required|integer|min:1|max:100',
        ]);

        $costume->addItems((int) $validated['count']);

        return redirect()->route('admin.costumes.show', $costume)
            ->with('success', "Added {$validated['count']} new items. Don't forget to print QR labels for them.");
    }

    // dzēš vienu tērpa vienību (tikai ja tā nav izsniegta)
    public function destroyItem(CostumeItem $item)
    {
        $this->authorize('update', $item->costume);

        if ($item->assigned_to) {
            return back()->with('error', "Item {$item->code} can't be deleted — it's assigned to a member.");
        }

        $code = $item->code;
        $costume = $item->costume;

        $item->delete();
        $costume->update(['quantity' => $costume->items()->count()]);

        return back()->with('success', "Item {$code} deleted.");
    }

    public function show(Costume $costume)
    {
        $this->authorize('view', $costume);

        $items = $costume->items()
            ->with(['user', 'assignments.assignedBy', 'assignments.returnedBy'])
            ->get();

        // studenti, kuriem skolotājs var izsniegt brīvu vienību
        $members = $costume->group->members()->orderBy('name')->get();

        return view('admin.costumes.show', compact('costume', 'items', 'members'));
    }

    // drukājama QR kodu lapa – visas tērpa vienības vienā A4 režģī
    public function labels(Costume $costume, Request $request)
    {
        $this->authorize('view', $costume);

        $filter = in_array($request->query('filter'), ['available', 'assigned'], true)
            ? $request->query('filter')
            : 'all';

        $columns = max(2, min(4, (int) $request->query('cols', 3)));

        $items = $costume->items()
            ->when($filter === 'available', fn ($query) => $query->whereNull('assigned_to'))
            ->when($filter === 'assigned', fn ($query) => $query->whereNotNull('assigned_to'))
            ->orderBy('code')
            ->get();

        return view('admin.costumes.labels', compact('costume', 'items', 'filter', 'columns'));
    }

    public function destroy(Costume $costume)
    {
        $this->authorize('delete', $costume);

        if ($costume->image) {
            Storage::disk('public')->delete($costume->image);
        }

        $costume->delete();

        return redirect()->route('admin.costumes.index')->with('success', "Costume “{$costume->name}” deleted.");
    }

    public function unassign(CostumeItem $item)
    {
        $this->authorize('unassignAsAdmin', $item);

        $item->release(auth()->user(), 'admin');

        return back()->with('success', "Item {$item->code} unassigned from the member.");
    }

    // skolotājs izsniedz brīvu vienību izvēlētam studentam (piem. mēģinājumā, bez QR skenēšanas)
    public function assign(Request $request, CostumeItem $item)
    {
        $this->authorize('assignAsAdmin', $item);

        $group = $item->costume->group;

        $validated = $request->validate([
            'user_id' => ['required', 'integer'],
        ]);

        // izsniegt drīkst tikai šīs grupas dalībniekam
        $student = $group->members()->whereKey($validated['user_id'])->first();
        abort_if(is_null($student), 422, 'That student is not in this group.');

        try {
            $item->assignTo($student, auth()->user());
        } catch (CostumeItemUnavailableException) {
            return back()->with('error', "Item {$item->code} was just taken by someone else.");
        }

        return back()->with('success', "Item {$item->code} handed out to {$student->name}.");
    }

    // izveido jaunu QR kodu vienībai, ja fiziskā birka ir pazaudēta vai bojāta
    public function regenerateQr(CostumeItem $item)
    {
        $this->authorize('regenerateQr', $item);

        $item->update(['qr_code' => Str::uuid()]);

        return back()->with('success', "A new QR code was generated for {$item->code}. Print and attach the new label — the old QR no longer works.");
    }
}
