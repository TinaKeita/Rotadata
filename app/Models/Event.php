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

    // grupas dalībnieki, kuri šajā koncertā NEpiedalās (pārējie piedalās pēc noklusējuma)
    public function absentees()
    {
        return $this->belongsToMany(User::class, 'event_absences')->withTimestamps();
    }

    // vienreiz nolasīti gatavības dati, lai costumeReadiness() un studentReadiness() neatkārto vaicājumus
    private ?array $readinessCache = null;

    /**
     * Koncertā piedalošies studenti (ar komplektu no group_user), nepieciešamie tērpi un tas,
     * kuru tērpu vienības katram studentam šobrīd ir rokās.
     */
    private function readinessData(): array
    {
        if ($this->readinessCache !== null) {
            return $this->readinessCache;
        }

        $absentIds = $this->absentees()->pluck('users.id');

        $students = $this->group->members()
            ->whereNotIn('users.id', $absentIds)
            ->orderBy('name')
            ->get();

        $costumes = $this->costumes;

        // studenta id => to tērpu id, kuru vismaz viena vienība viņam ir izsniegta
        $holdings = CostumeItem::whereIn('costume_id', $costumes->pluck('id'))
            ->whereIn('assigned_to', $students->pluck('id'))
            ->get(['costume_id', 'assigned_to'])
            ->groupBy('assigned_to')
            ->map(fn ($rows) => $rows->pluck('costume_id')->map(fn ($id) => (int) $id)->unique()->all());

        return $this->readinessCache = [
            'students' => $students,
            'costumes' => $costumes,
            'holdings' => $holdings,
            'absentCount' => $absentIds->count(),
        ];
    }

    // studenta komplekta id šajā grupā (null, ja komplekts nav izvēlēts)
    private static function setIdOf(User $student): ?int
    {
        return $student->pivot?->costume_set_id ? (int) $student->pivot->costume_set_id : null;
    }

    // kopīgs tērps (bez komplekta) vajadzīgs visiem, komplekta tērps – tikai šī komplekta studentiem
    private static function needs(Costume $costume, ?int $setId): bool
    {
        return is_null($costume->costume_set_id) || (int) $costume->costume_set_id === $setId;
    }

    /**
     * Cik piedalošos studentu, kam šis tērps vajadzīgs, jau to tur rokās, salīdzinot ar vajadzīgo skaitu,
     * un vai vispār inventārā ir pietiekami daudz vienību, lai to sasniegtu.
     * Balstās uz esošo izsniegšanas stāvokli — skolotājam nekas manuāli nav jāskaita.
     * Ja koncertam nav norādīts konkrēts skaits (target_count), vajadzīgs visiem piedalošajiem, kam tērps paredzēts.
     */
    public function costumeReadiness(): \Illuminate\Support\Collection
    {
        ['students' => $students, 'costumes' => $costumes, 'holdings' => $holdings] = $this->readinessData();

        return $costumes->map(function (Costume $costume) use ($students, $holdings) {
            $needing = $students->filter(fn (User $s) => self::needs($costume, self::setIdOf($s)));

            $target = $costume->pivot->target_count ?: $needing->count();
            $total = $costume->quantity; // cik vienību šim tērpam vispār ir inventārā

            $assigned = $needing
                ->filter(fn (User $s) => in_array($costume->id, $holdings[$s->id] ?? [], true))
                ->count();

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

    /**
     * Gatavība pa studentiem: students ir gatavs tikai tad, ja viņam ir rokās pa vienai vienībai
     * no KATRA vajadzīgā tērpa (kopīgie + viņa komplekta tērpi).
     * Ja koncertam ir komplektu tērpi, bet studentam komplekts nav izvēlēts, viņš netiek skaitīts kā gatavs.
     */
    public function studentReadiness(): array
    {
        ['students' => $students, 'costumes' => $costumes, 'holdings' => $holdings, 'absentCount' => $absentCount] = $this->readinessData();

        $hasSetCostumes = $costumes->contains(fn (Costume $c) => ! is_null($c->costume_set_id));

        $rows = $students->map(function (User $student) use ($costumes, $holdings, $hasSetCostumes) {
            $setId = self::setIdOf($student);
            $held = $holdings[$student->id] ?? [];

            $needed = $costumes->filter(fn (Costume $c) => self::needs($c, $setId))->values();
            $missing = $needed->reject(fn (Costume $c) => in_array($c->id, $held, true))->values();
            $noSet = $hasSetCostumes && is_null($setId);

            return [
                'student' => $student,
                'needed' => $needed,
                'missing' => $missing,
                'no_set' => $noSet,
                'ready' => ! $noSet && $missing->isEmpty(),
            ];
        });

        $total = $rows->count();
        $ready = $rows->where('ready', true)->count();

        return [
            'students' => $rows,
            'ready' => $ready,
            'total' => $total,
            'percent' => $total > 0 ? (int) round($ready / $total * 100) : 0,
            'absentCount' => $absentCount,
            'hasCostumes' => $costumes->isNotEmpty(),
        ];
    }

    /**
     * Viena studenta gatavība šim koncertam (tā pati rinda, ko skolotājs redz studentReadiness()),
     * vai null, ja students koncertā nepiedalās vai nav grupas dalībnieks.
     */
    public function readinessFor(User $user): ?array
    {
        return $this->studentReadiness()['students']
            ->first(fn (array $row) => $row['student']->id === $user->id);
    }

    // vai skolotājs šo studentu atzīmējis kā nepiedalošos
    public function isAbsent(User $user): bool
    {
        return $this->absentees()->whereKey($user->id)->exists();
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
