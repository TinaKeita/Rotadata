<?php

namespace App\Support;

use App\Models\CostumeItem;
use App\Models\CostumeItemAssignment;
use App\Models\Group;
use Illuminate\Support\Collection;

// grupas tērpu aprites rādītāji paneļa kopsavilkumam un sadaļai "Nothing spare"
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

    public function activityToday(): array
    {
        return $this->activitySince(now()->startOfDay());
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

}
