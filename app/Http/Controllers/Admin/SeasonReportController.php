<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Costume;
use App\Models\CostumeItemAssignment;
use App\Support\Season;
use Illuminate\Support\Collection;

class SeasonReportController extends Controller
{
    // drukājama sezonas atskaite: kas vēl nav atdots un kā šosezon izmantoti tērpi
    public function show()
    {
        $group = auth()->user()->adminGroups()->first();
        abort_if(is_null($group), 403, 'You do not have a group yet.');

        $season = [
            'label' => Season::label(),
            'start' => Season::start(),
            'end' => Season::end(),
        ];

        $checklist = $this->returnChecklist($group);
        $usage = $this->costumeUsage($group, $season['start'], $season['end']);

        return view('admin.season-report.show', [
            'group' => $group,
            'season' => $season,
            'generatedAt' => now(),
            'checklist' => $checklist,
            'usage' => $usage,
        ]);
    }

    // vēl neatdotās vienības ar to, cik ilgi tās ir prom — jāsavāc pirms vasaras brīvlaika
    private function returnChecklist($group): Collection
    {
        return $group->costumeItems()
            ->whereNotNull('assigned_to')
            ->with(['user', 'costume'])
            ->get()
            ->map(fn ($item) => [
                'code' => $item->code,
                'costume' => $item->costume?->name,
                'who' => $item->user?->name ?? 'Unknown',
                'days' => (int) $item->assigned_at->diffInDays(now()),
            ])
            ->sortByDesc('days')
            ->values();
    }

    // katra tērpa izmantojums šosezon: cik reižu izsniegts, cik dažādiem studentiem un vidēji cik ilgi
    private function costumeUsage($group, $start, $end): Collection
    {
        return $group->costumes()->withCount('items')->get()->map(function (Costume $costume) use ($start, $end) {
            $assignments = CostumeItemAssignment::whereIn('costume_item_id', $costume->items()->pluck('id'))
                ->whereBetween('assigned_at', [$start, $end])
                ->get();

            $students = $assignments
                ->map(fn ($a) => $a->user_id ?? 'name:'.$a->user_name)
                ->unique()
                ->count();

            $avgDays = $assignments->isEmpty() ? null : round(
                $assignments->avg(fn ($a) => $a->assigned_at->diffInDays($a->returned_at ?? now())),
                1
            );

            return [
                'name' => $costume->name,
                'items' => $costume->items_count,
                'checkouts' => $assignments->count(),
                'students' => $students,
                'avg_days' => $avgDays,
            ];
        })->sortByDesc('checkouts')->values();
    }
}
