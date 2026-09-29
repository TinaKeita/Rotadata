<?php

namespace App\Support;

use App\Models\CostumeItem;
use App\Models\CostumeItemAssignment;
use App\Models\Group;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

// grupas tērpu aprites statistika – kopīga panelim (kopsavilkuma josla) un aktivitātes lapai
class GroupActivity
{
    private Collection $itemIds;

    public function __construct(private Group $group)
    {
        $this->itemIds = CostumeItem::whereHas('costume', fn ($q) => $q->where('group_id', $group->id))->pluck('id');
    }

    public function overview(): array
    {
        $total = $this->itemIds->count();
        $out = CostumeItem::whereIn('id', $this->itemIds)->whereNotNull('assigned_to')->count();

        return [
            'totalItems'    => $total,
            'itemsOut'      => $out,
            'available'     => max(0, $total - $out),
            'utilisation'   => $total > 0 ? (int) round($out / $total * 100) : 0,
            'memberCount'   => $this->group->members()->count(),
            'equippedCount' => CostumeItem::whereIn('id', $this->itemIds)
                ->whereNotNull('assigned_to')
                ->distinct()
                ->count('assigned_to'),
        ];
    }

    // izsniegtās un atdotās vienības kopš $since
    public function activitySince($since): array
    {
        return [
            'assigned' => CostumeItemAssignment::whereIn('costume_item_id', $this->itemIds)
                ->where('assigned_at', '>=', $since)->count(),
            'returned' => CostumeItemAssignment::whereIn('costume_item_id', $this->itemIds)
                ->where('returned_at', '>=', $since)->count(),
        ];
    }

    public function activityThisWeek(): array
    {
        return $this->activitySince(now()->subDays(7));
    }

    public function activityToday(): array
    {
        return $this->activitySince(now()->startOfDay());
    }

    // cik vienību ir atpakaļ krātuvē (vecais "Collection readiness" rādītājs)
    public function readiness(): array
    {
        $total = $this->itemIds->count();
        $out = CostumeItem::whereIn('id', $this->itemIds)->whereNotNull('assigned_to')->count();
        $back = max(0, $total - $out);

        return [
            'percent' => $total > 0 ? (int) round($back / $total * 100) : 100,
            'back'    => $back,
            'out'     => $out,
            'total'   => $total,
        ];
    }

    public function recentActivity(int $limit = 10)
    {
        $window = now()->subDays(45);

        $rows = CostumeItemAssignment::with(['item.costume', 'returnedBy'])
            ->whereIn('costume_item_id', $this->itemIds)
            ->where(fn ($q) => $q->where('assigned_at', '>=', $window)->orWhere('returned_at', '>=', $window))
            ->get();

        // nodošanas "pieņemšanas puse": vienība + brīdis, kad tā tika nodota tālāk
        // tos "paņēma" ierakstus izlaižam, lai plūsmā par nodošanu būtu tikai viens (zilais) ieraksts
        $handoverKeys = $rows
            ->where('return_note', 'transfer')
            ->filter(fn ($a) => $a->returned_at)
            ->map(fn ($a) => $a->costume_item_id.'|'.$a->returned_at->timestamp)
            ->all();

        return $rows
            ->flatMap(function (CostumeItemAssignment $a) use ($window, $handoverKeys) {
                $events = [];

                $isHandoverPickup = in_array($a->costume_item_id.'|'.$a->assigned_at?->timestamp, $handoverKeys, true);

                if ($a->assigned_at >= $window && ! $isHandoverPickup) {
                    $events[] = [
                        'at'      => $a->assigned_at,
                        'type'    => 'assigned',
                        'code'    => $a->item?->code,
                        'costume' => $a->item?->costume?->name,
                        'who'     => $a->user_name,
                        'actor'   => null,
                    ];
                }

                if ($a->returned_at && $a->returned_at >= $window) {
                    $events[] = [
                        'at'      => $a->returned_at,
                        'type'    => match ($a->return_note) {
                            'admin' => 'taken_back',
                            'transfer' => 'handed_over',
                            'removed' => 'freed',
                            'left_group' => 'left_group',
                            default => 'returned',
                        },
                        'code'    => $a->item?->code,
                        'costume' => $a->item?->costume?->name,
                        'who'     => $a->user_name,
                        'actor'   => $a->returnedBy?->name,
                    ];
                }

                return $events;
            })
            ->sortByDesc('at')
            ->take($limit)
            ->values();
    }

    public function longestOut(int $limit = 5)
    {
        return CostumeItemAssignment::with('item.costume')
            ->whereIn('costume_item_id', $this->itemIds)
            ->whereNull('returned_at')
            ->orderBy('assigned_at')
            ->take($limit)
            ->get()
            ->map(fn (CostumeItemAssignment $a) => [
                'code'    => $a->item?->code,
                'costume' => $a->item?->costume?->name,
                'who'     => $a->user_name,
                'user_id' => $a->user_id,
                'days'    => (int) $a->assigned_at->diffInDays(now()),
            ]);
    }

    public function topHolders(int $limit = 5)
    {
        $rows = DB::table('costume_items')
            ->whereIn('id', $this->itemIds)
            ->whereNotNull('assigned_to')
            ->select('assigned_to', DB::raw('count(*) as held'))
            ->groupBy('assigned_to')
            ->orderByDesc('held')
            ->limit($limit)
            ->get();

        $names = User::whereIn('id', $rows->pluck('assigned_to'))->pluck('name', 'id');

        return $rows->map(fn ($r) => [
            'id'   => (int) $r->assigned_to,
            'name' => $names[$r->assigned_to] ?? 'Unknown',
            'held' => (int) $r->held,
        ]);
    }

    // tērpi, kuriem visas vienības jau izsniegtas
    public function fullyOut()
    {
        return $this->group->costumes()
            ->withCount([
                'items as items_total',
                'items as items_out' => fn ($q) => $q->whereNotNull('assigned_to'),
            ])
            ->get()
            ->filter(fn ($c) => $c->items_total > 0 && $c->items_total === $c->items_out)
            ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'count' => $c->items_total])
            ->values();
    }

    public function inDemand(int $limit = 4)
    {
        return $this->group->costumes()
            ->withCount([
                'items as items_total',
                'items as items_out' => fn ($q) => $q->whereNotNull('assigned_to'),
            ])
            ->get()
            ->filter(fn ($c) => $c->items_total > 0)
            ->map(fn ($c) => [
                'name'    => $c->name,
                'out'     => $c->items_out,
                'total'   => $c->items_total,
                'percent' => (int) round($c->items_out / $c->items_total * 100),
            ])
            ->sortByDesc('percent')
            ->take($limit)
            ->values();
    }

    public function mostTravelled()
    {
        $row = CostumeItemAssignment::whereIn('costume_item_id', $this->itemIds)
            ->select('costume_item_id', DB::raw('count(distinct user_id) as travellers'))
            ->groupBy('costume_item_id')
            ->orderByDesc('travellers')
            ->first();

        if (! $row || $row->travellers < 2) {
            return null;
        }

        $item = CostumeItem::with('costume')->find($row->costume_item_id);

        return [
            'code'       => $item?->code,
            'costume'    => $item?->costume?->name,
            'travellers' => (int) $row->travellers,
        ];
    }

    public function busiestCostume()
    {
        $row = CostumeItemAssignment::query()
            ->whereIn('costume_item_id', $this->itemIds)
            ->where('costume_item_assignments.assigned_at', '>=', now()->subDays(120))
            ->join('costume_items', 'costume_items.id', '=', 'costume_item_assignments.costume_item_id')
            ->join('costumes', 'costumes.id', '=', 'costume_items.costume_id')
            ->select('costumes.name', DB::raw('count(*) as times'))
            ->groupBy('costumes.name')
            ->orderByDesc('times')
            ->first();

        return $row ? ['name' => $row->name, 'times' => (int) $row->times] : null;
    }

    public function weeklyActivity()
    {
        return collect(range(5, 0))->map(function ($weeksAgo) {
            $start = now()->startOfWeek()->subWeeks($weeksAgo);
            $end = (clone $start)->addWeek();

            return [
                'label'    => $start->format('d.m'),
                'assigned' => CostumeItemAssignment::whereIn('costume_item_id', $this->itemIds)
                    ->whereBetween('assigned_at', [$start, $end])->count(),
                'returned' => CostumeItemAssignment::whereIn('costume_item_id', $this->itemIds)
                    ->whereBetween('returned_at', [$start, $end])->count(),
            ];
        });
    }
}
