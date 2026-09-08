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
        $costumes = $group
            ? $group->costumes()->withCount(['items', 'items as items_out_count' => fn ($q) => $q->whereNotNull('assigned_to')])->get()
            : collect();

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

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'quantity' => 'required|integer|min:1|max:200',
        ]);

        $costume = Costume::create([
            'name' => $validated['name'],
            'quantity' => 0,
            'image' => null,
            'group_id' => $group->id,
        ]);

        $costume->addItems((int) $validated['quantity']);

        return redirect()->route('admin.costumes.index')->with('success', "Tērps “{$costume->name}” izveidots ar {$validated['quantity']} vienībām.");
    }

    // forma tērpa nosaukuma rediģēšanai
    public function edit(Costume $costume)
    {
        $this->authorize('update', $costume);

        return view('admin.costumes.edit', compact('costume'));
    }

    public function update(Request $request, Costume $costume)
    {
        $this->authorize('update', $costume);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $costume->update(['name' => $validated['name']]);

        return redirect()->route('admin.costumes.show', $costume)
            ->with('success', "Tērpa nosaukums nomainīts uz “{$costume->name}”.");
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
            ->with('success', "Pievienotas {$validated['count']} jaunas vienības. Neaizmirstiet izdrukāt tām QR birkas.");
    }

    // dzēš vienu tērpa vienību (tikai ja tā nav izsniegta)
    public function destroyItem(CostumeItem $item)
    {
        $this->authorize('update', $item->costume);

        if ($item->assigned_to) {
            return back()->with('error', "Vienību {$item->code} nevar dzēst — tā ir izsniegta dalībniekam.");
        }

        $code = $item->code;
        $costume = $item->costume;

        $item->delete();
        $costume->update(['quantity' => $costume->items()->count()]);

        return back()->with('success', "Vienība {$code} dzēsta.");
    }

    public function show(Costume $costume)
    {
        $this->authorize('view', $costume);

        $items = $costume->items()
            ->with(['user', 'assignments.assignedBy', 'assignments.returnedBy'])
            ->get();

        return view('admin.costumes.show', compact('costume', 'items'));
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

        $costume->delete();

        return redirect()->route('admin.costumes.index')->with('success', "Tērps “{$costume->name}” dzēsts.");
    }

    public function unassign(CostumeItem $item)
    {
        $this->authorize('unassignAsAdmin', $item);

        $item->release(auth()->user(), 'admin');

        return back()->with('success', "Vienība {$item->code} noņemta no dalībnieka.");
    }

    // izveido jaunu QR kodu vienībai, ja fiziskā birka ir pazaudēta vai bojāta
    public function regenerateQr(CostumeItem $item)
    {
        $this->authorize('regenerateQr', $item);

        $item->update(['qr_code' => Str::uuid()]);

        return back()->with('success', "Vienībai {$item->code} izveidots jauns QR kods. Izdrukājiet un pielīmējiet jauno birku — vecais QR vairs nedarbojas.");
    }
}
