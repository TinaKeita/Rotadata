<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = [
        'group_id',
        'title',
        'starts_at',
        'location',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
    ];

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // nepieciešamie tērpu veidi šim koncertam (neobligāti, pievienojami vēlāk)
    public function costumes()
    {
        return $this->belongsToMany(Costume::class, 'event_costume')
            ->withPivot('note', 'target_count')
            ->withTimestamps();
    }

    /**
     * Cik studentiem katrs nepieciešamais tērps jau ir izsniegts, salīdzinot ar to, cik vajadzīgs,
     * un vai vispār inventārā ir pietiekami daudz vienību, lai to sasniegtu.
     * Balstās uz esošo izsniegšanas stāvokli — skolotājam nekas manuāli nav jāskaita.
     * Ja koncertam nav norādīts konkrēts skaits (target_count), pieņemam, ka vajadzīgs visai grupai.
     */
    public function costumeReadiness(): \Illuminate\Support\Collection
    {
        $memberCount = $this->group->members()->count();

        return $this->costumes->map(function (Costume $costume) use ($memberCount) {
            $target = $costume->pivot->target_count ?: $memberCount;
            $total = $costume->quantity; // cik vienību šim tērpam vispār ir inventārā

            $assigned = $costume->items()
                ->whereNotNull('assigned_to')
                ->distinct()
                ->count('assigned_to');

            return [
                'costume' => $costume,
                'assigned' => $assigned,
                'target' => $target,
                'total' => $total,
                // par cik vienību pietrūkst inventārā, lai vispār varētu sasniegt vajadzīgo skaitu
                'shortfall' => max(0, $target - $total),
                'percent' => $target > 0 ? min(100, (int) round($assigned / $target * 100)) : 100,
            ];
        });
    }

    // vēl nepienākuši koncerti, tuvākais pirmais
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('starts_at', '>=', now())->orderBy('starts_at');
    }

    // jau notikuši koncerti, jaunākais pirmais
    public function scopePast(Builder $query): Builder
    {
        return $query->where('starts_at', '<', now())->orderByDesc('starts_at');
    }
}
