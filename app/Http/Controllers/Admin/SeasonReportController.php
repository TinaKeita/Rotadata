<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Costume;
use App\Models\CostumeItemAssignment;
use App\Models\Event;
use App\Models\Group;
use App\Models\User;
use App\Support\Season;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Drukājama sezonas atskaite – tikai tas, kas skolotājam var noderēt vēlāk:
 * kas vēl jāsavāc, inventāra stāvoklis, koncerti ar gatavību koncerta dienā un komplekti.
 */
class SeasonReportController extends Controller
{
    public function show(Request $request)
    {
        $group = $this->group();
        $season = $this->season($request, $group);

        // atskaite pēc stāvokļa sezonas beigās, bet pašreizējai sezonai – pēc šodienas
        $asOf = $season['current'] ? now() : $season['end'];

        $outstanding = $this->outstanding($group, $asOf);

        return view('admin.season-report.show', [
            'group' => $group,
            'season' => $season,
            'seasons' => $this->availableSeasons($group),
            'asOf' => $asOf,
            'generatedAt' => now(),
            'outstanding' => $outstanding,
            'inventory' => $this->inventory($group, $asOf),
            'concerts' => $this->concerts($group, $season['start'], $season['end'], $asOf),
            'sets' => $group->costumeSets()->withCount('costumes')->get()
                ->map(fn ($set) => [
                    'name' => $set->name,
                    'costumes' => $set->costumes_count,
                    'students' => $group->activeMembers()->wherePivot('costume_set_id', $set->id)->count(),
                ]),
        ]);
    }

    private function group(): Group
    {
        $group = auth()->user()->currentGroup();
        abort_if(is_null($group), 403, 'You do not have a group yet.');

        return $group;
    }

    // izvēlētā sezona (?season=2025) vai pašreizējā
    private function season(Request $request, Group $group): array
    {
        $year = $request->integer('season') ?: null;
        $years = $this->availableSeasons($group)->pluck('year');

        return Season::bounds($year && $years->contains($year) ? $year : null);
    }

    // sezonas no grupas izveides līdz šodienai, jaunākā pirmā
    private function availableSeasons(Group $group): Collection
    {
        $first = Season::startYearOf($group->created_at ?? now());
        $current = Season::startYearOf(now());

        return collect(range($current, min($first, $current)))->map(fn ($y) => Season::bounds($y));
    }



    // vienības, kas atskaites brīdī vēl nav atdotas, sagrupētas pa studentiem – savākšanas saraksts
    private function outstanding(Group $group, Carbon $asOf): Collection
    {
        $members = $group->members()->get()->keyBy('id');
        $setNames = $group->costumeSets()->pluck('name', 'id');

        return CostumeItemAssignment::with('item.costume')
            ->whereIn('costume_item_id', $group->costumeItems()->pluck('id'))
            ->where('assigned_at', '<=', $asOf)
            ->where(fn ($q) => $q->whereNull('returned_at')->orWhere('returned_at', '>', $asOf))
            ->get()
            ->groupBy(fn ($a) => $a->user_id ?? 'name:'.$a->user_name)
            ->map(function ($rows) use ($members, $setNames, $asOf) {
                $first = $rows->first();
                $member = $members->get($first->user_id);
                $user = $member ?? ($first->user_id ? User::withTrashed()->find($first->user_id) : null);

                return [
                    'user_id' => $first->user_id,
                    'name' => $first->user_name,
                    'email' => $user?->email,
                    'set' => $member ? ($setNames[$member->pivot->costume_set_id] ?? null) : null,
                    // vai students vēl ir grupā – citādi vienības jāsavāc citādi
                    'status' => $member ? 'in group' : ($user?->trashed() ? 'account removed' : 'left the group'),
                    'items' => $rows->sortBy(fn ($a) => $a->item?->code)->map(fn ($a) => [
                        'code' => $a->item?->code,
                        'costume' => $a->item?->costume?->name,
                        'since' => $a->assigned_at,
                        'days' => (int) $a->assigned_at->diffInDays($asOf),
                    ])->values(),
                    'longest' => (int) $rows->max(fn ($a) => $a->assigned_at->diffInDays($asOf)),
                ];
            })
            ->sortByDesc('longest')
            ->values();
    }

    // koncerti sezonā ar dalībniekiem un gatavību koncerta dienā (pagātnes koncertiem – pēc vēstures)
    private function concerts(Group $group, Carbon $from, Carbon $to, Carbon $asOf): Collection
    {
        return $group->events()
            ->with(['costumes', 'group', 'studentCostumes'])
            ->whereBetween('starts_at', [$from, $to])
            ->orderBy('starts_at')
            ->get()
            ->map(function (Event $event) use ($asOf) {
                $held = $event->starts_at->lte($asOf);
                $view = $held ? $event->asOf($event->starts_at) : $event;
                $readiness = $view->studentReadiness();

                return [
                    'event' => $event,
                    'held' => $held,
                    'readiness' => $readiness,
                    'notReady' => $readiness['students']->where('ready', false)->values(),
                    'extras' => $event->studentCostumes->count(),
                ];
            });
    }

    // inventārs atskaites brīdī: cik vienību katram tērpam ir, cik no tām ārā un cik brīvas
    private function inventory(Group $group, Carbon $asOf): Collection
    {
        return $group->costumes()->with(['costumeSet', 'items'])->orderBy('name')->get()
            ->map(function (Costume $costume) use ($asOf) {
                $out = CostumeItemAssignment::whereIn('costume_item_id', $costume->items->pluck('id'))
                    ->where('assigned_at', '<=', $asOf)
                    ->where(fn ($q) => $q->whereNull('returned_at')->orWhere('returned_at', '>', $asOf))
                    ->count();

                return [
                    'name' => $costume->name,
                    'set' => $costume->costumeSet?->name,
                    'items' => $costume->items->count(),
                    'out' => $out,
                    'free' => max(0, $costume->items->count() - $out),
                ];
            });
    }






}
