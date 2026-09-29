<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index()
    {
        $group = auth()->user()->adminGroups()->first();

        $upcoming = $group ? $group->events()->with(['costumes', 'group'])->upcoming()->get() : collect();
        $past = $group ? $group->events()->with(['costumes', 'group'])->past()->get() : collect();

        return view('admin.events.index', compact('upcoming', 'past'));
    }

    // forma jauna koncerta pievienošanai
    public function create()
    {
        $group = auth()->user()->adminGroups()->first();
        abort_if(is_null($group), 403, 'You do not have a group yet.');

        $costumes = $group->costumes()->with('costumeSet')->get();
        $members = $group->members()->orderBy('name')->get();
        $sets = $group->costumeSets;

        return view('admin.events.create', compact('costumes', 'members', 'sets'));
    }

    public function store(Request $request)
    {
        $group = auth()->user()->adminGroups()->first();
        abort_if(is_null($group), 403, 'You do not have a group yet.');

        $validated = $this->validated($request, $group->id, null);

        $event = Event::create([
            'group_id' => $group->id,
            'title' => $validated['title'],
            'starts_at' => $validated['starts_at'],
            'location' => $validated['location'],
            'notes' => $validated['notes'],
            'created_by' => auth()->id(),
        ]);

        $this->syncCostumes($event, $request);
        $this->syncAbsences($event, $request);

        return redirect()->route('admin.events.index')->with('success', "Concert “{$event->title}” added.");
    }

    // forma koncerta rediģēšanai
    public function edit(Event $event)
    {
        $this->authorize('update', $event);

        $costumes = $event->group->costumes()->with('costumeSet')->get();
        $members = $event->group->members()->orderBy('name')->get();
        $sets = $event->group->costumeSets;
        $event->load('absentees');

        return view('admin.events.edit', compact('event', 'costumes', 'members', 'sets'));
    }

    public function update(Request $request, Event $event)
    {
        $this->authorize('update', $event);

        $validated = $this->validated($request, $event->group_id, $event);

        $event->update([
            'title' => $validated['title'],
            'starts_at' => $validated['starts_at'],
            'location' => $validated['location'],
            'notes' => $validated['notes'],
        ]);

        $this->syncCostumes($event, $request);
        $this->syncAbsences($event, $request);

        return redirect()->route('admin.events.index')->with('success', "Concert “{$event->title}” updated.");
    }

    public function destroy(Event $event)
    {
        $this->authorize('delete', $event);

        $title = $event->title;
        $event->delete();

        return redirect()->route('admin.events.index')->with('success', "Concert “{$title}” deleted.");
    }

    // $event ir null, veidojot jaunu koncertu; rediģējot dod iespēju saglabāt jau pagātnē esošu
    // datumu, ja tas netiek mainīts (piem. rediģē tikai piezīmes vecam koncertam)
    private function validated(Request $request, int $groupId, ?Event $event): array
    {
        return $request->validate([
            'title' => 'required|string|max:255',
            'starts_at' => [
                'required',
                'date',
                function (string $attribute, $value, \Closure $fail) use ($event) {
                    $startsAt = \Illuminate\Support\Carbon::parse($value);

                    if ($startsAt->lt(now()) && (! $event || ! $startsAt->eq($event->starts_at))) {
                        $fail('The concert date and time must be in the future.');
                    }
                },
            ],
            'location' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
            'costume_ids' => 'nullable|array',
            'costume_ids.*' => 'integer|exists:costumes,id,group_id,'.$groupId,
            'costume_notes' => 'nullable|array',
            'costume_notes.*' => 'nullable|string|max:255',
            'costume_targets' => 'nullable|array',
            'costume_targets.*' => 'nullable|integer|min:1|max:1000',
            'attendance_sent' => 'nullable|boolean',
            'attending_ids' => 'nullable|array',
            'attending_ids.*' => 'integer',
        ]);
    }

    // pievieno/atjauno nepieciešamo tērpu sarakstu koncertam (neobligāts lauks)
    private function syncCostumes(Event $event, Request $request): void
    {
        $ids = $request->input('costume_ids', []);
        $notes = $request->input('costume_notes', []);
        $targets = $request->input('costume_targets', []);

        $sync = collect($ids)
            ->mapWithKeys(fn ($id) => [(int) $id => [
                'note' => $notes[$id] ?? null,
                'target_count' => $targets[$id] ?? null,
            ]])
            ->all();

        $event->costumes()->sync($sync);
    }

    // saglabā, kuri grupas dalībnieki koncertā NEpiedalās – formā tie ir neatzīmētie
    // (attendance_sent pasargā no visu izslēgšanas, ja forma sarakstu vispār nesūtīja)
    private function syncAbsences(Event $event, Request $request): void
    {
        if (! $request->boolean('attendance_sent')) {
            return;
        }

        $attending = collect($request->input('attending_ids', []))->map(fn ($id) => (int) $id);

        $absentIds = $event->group->members()
            ->pluck('users.id')
            ->reject(fn ($id) => $attending->contains((int) $id))
            ->values()
            ->all();

        $event->absentees()->sync($absentIds);
    }
}
