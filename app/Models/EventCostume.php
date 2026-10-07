<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

// koncerta un tērpa saite. group_id ņem no koncerta – datubāzes ārējās atslēgas tad garantē,
// ka koncertam var piesaistīt tikai tās pašas grupas tērpu
class EventCostume extends Pivot
{
    protected $table = 'event_costume';

    public $incrementing = true;

    protected static function booted(): void
    {
        static::creating(function (EventCostume $pivot) {
            $pivot->group_id ??= Event::whereKey($pivot->event_id)->value('group_id');
        });
    }
}
