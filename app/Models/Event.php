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

    // papildu tērpi konkrētiem studentiem (piem. solistam), kas nāk klāt komplekta prasībām
    public function studentCostumes()
    {
        return $this->hasMany(EventStudentCostume::class);
    }

    // vienreiz nolasīti gatavības dati, lai costumeReadiness() un studentReadiness() neatkārto vaicājumus
    private ?array $readinessCache = null;

    // ja uzstādīts – gatavību rēķina pēc tā, kas studentiem bija rokās šajā brīdī (no izsniegšanas vēstures)
    private ?\Illuminate\Support\Carbon $holdingsAt = null;

    /**
     * Šī koncerta kopija, kuras gatavība rēķināta pēc stāvokļa noteiktā brīdī (piem. koncerta dienā),
     * nevis pēc šodienas. Studenti, komplekti un neapmeklētāji ir pašreizējie – vēsturē tie netiek glabāti.
     */
    public function asOf(\Illuminate\Support\Carbon $at): static
    {
        $copy = clone $this;
        $copy->holdingsAt = $at;
        $copy->readinessCache = null;

        return $copy;
    }

    /**
     * Koncertā piedalošies studenti (ar komplektu no group_user), visi iesaistītie tērpi
     * (visiem vajadzīgie + studentu papildu tērpi), cik katram studentam vajag katra tērpa
     * un cik vienību no tā viņam šobrīd ir rokās.
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

        // papildu tērpi tikai piedalošajiem studentiem: studenta id => [tērpa id => skaits]
        $extraRows = $this->studentCostumes()->whereIn('user_id', $students->pluck('id'))->get();
        $extras = $extraRows->groupBy('user_id')
            ->map(fn ($rows) => $rows->mapWithKeys(fn ($r) => [(int) $r->costume_id => (int) $r->quantity])->all());

        // visiem vajadzīgie tērpi + tie, kas vajadzīgi tikai kā papildu tērpi
        $general = $this->costumes;
        $extraOnlyIds = $extraRows->pluck('costume_id')->map(fn ($id) => (int) $id)->unique()->diff($general->pluck('id'));
        $costumes = $general->concat(Costume::whereIn('id', $extraOnlyIds)->orderBy('name')->get())->values();

        // studenta id => [tērpa id => cik vienību rokās]
        $holdingRows = $this->holdingsAt
            // vēsturiskais stāvoklis: vienības, kas tajā brīdī bija izsniegtas un vēl nebija atdotas
            ? CostumeItemAssignment::query()
                ->join('costume_items', 'costume_items.id', '=', 'costume_item_assignments.costume_item_id')
                ->whereIn('costume_items.costume_id', $costumes->pluck('id'))
                ->whereIn('costume_item_assignments.user_id', $students->pluck('id'))
                ->where('costume_item_assignments.assigned_at', '<=', $this->holdingsAt)
                ->where(fn ($q) => $q->whereNull('costume_item_assignments.returned_at')
                    ->orWhere('costume_item_assignments.returned_at', '>', $this->holdingsAt))
                ->get(['costume_items.costume_id', 'costume_item_assignments.user_id as assigned_to'])
            : CostumeItem::whereIn('costume_id', $costumes->pluck('id'))
                ->whereIn('assigned_to', $students->pluck('id'))
                ->get(['costume_id', 'assigned_to']);

        $holdings = $holdingRows
            ->groupBy('assigned_to')
            ->map(fn ($rows) => $rows->countBy(fn ($r) => (int) $r->costume_id)->all());

        return $this->readinessCache = [
            'students' => $students,
            'general' => $general,
            'costumes' => $costumes,
            'extras' => $extras,
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
     * Cik vienību no katra tērpa vajag šim studentam: pa vienai no visiem viņam paredzētajiem tērpiem
     * plus viņa papildu tērpi (tie var arī palielināt jau vajadzīga tērpa skaitu, piem. solistam 2 vainagi).
     * Atgriež [tērpa id => skaits].
     */
    private function requiredFor(User $student, array $data): array
    {
        $required = [];

        foreach ($data['general'] as $costume) {
            if (self::needs($costume, self::setIdOf($student))) {
                $required[$costume->id] = 1;
            }
        }

        foreach ($data['extras'][$student->id] ?? [] as $costumeId => $quantity) {
            $required[$costumeId] = ($required[$costumeId] ?? 0) + $quantity;
        }

        return $required;
    }

    /**
     * Pa tērpiem: cik vienību piedalošajiem studentiem kopā vajag (komplekti + papildu tērpi), cik no tām
     * jau ir rokās un vai inventārā vispār pietiek vienību. Skaits veidojas automātiski no dalībniekiem –
     * skolotājam nekas manuāli nav jāskaita.
     */
    public function costumeReadiness(): \Illuminate\Support\Collection
    {
        $data = $this->readinessData();
        $required = $data['students']->mapWithKeys(fn (User $s) => [$s->id => $this->requiredFor($s, $data)]);
        $generalIds = $data['general']->pluck('id')->all();

        return $data['costumes']->map(function (Costume $costume) use ($data, $required, $generalIds) {
            $target = 0;
            $assigned = 0;

            foreach ($data['students'] as $student) {
                $need = $required[$student->id][$costume->id] ?? 0;
                $target += $need;
                $assigned += min($need, $data['holdings'][$student->id][$costume->id] ?? 0);
            }

            $total = $costume->quantity; // cik vienību šim tērpam vispār ir inventārā

            return [
                'costume' => $costume,
                // tērps vajadzīgs tikai kā papildu tērps atsevišķiem studentiem
                'extra_only' => ! in_array($costume->id, $generalIds, true),
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
     * Gatavība pa studentiem: students ir gatavs tikai tad, ja viņam ir rokās tik vienību no KATRA vajadzīgā tērpa,
     * cik vajag (kopīgie + viņa komplekta tērpi + viņa papildu tērpi).
     * Ja koncertam ir komplektu tērpi, bet studentam komplekts nav izvēlēts, viņš netiek skaitīts kā gatavs.
     */
    public function studentReadiness(): array
    {
        $data = $this->readinessData();
        $costumesById = $data['costumes']->keyBy('id');

        $hasSetCostumes = $data['general']->contains(fn (Costume $c) => ! is_null($c->costume_set_id));

        $rows = $data['students']->map(function (User $student) use ($data, $costumesById, $hasSetCostumes) {
            $required = $this->requiredFor($student, $data);
            $held = $data['holdings'][$student->id] ?? [];
            $extras = $data['extras'][$student->id] ?? [];

            // katram vajadzīgajam tērpam: cik vajag, cik ir un vai tas ir papildu tērps
            $checklist = collect($required)->map(fn ($qty, $id) => [
                'costume' => $costumesById[$id],
                'required' => $qty,
                'held' => min($qty, $held[$id] ?? 0),
                'extra' => isset($extras[$id]),
            ])->values();

            $missingList = $checklist->filter(fn ($c) => $c['held'] < $c['required'])->values();
            $noSet = $hasSetCostumes && is_null(self::setIdOf($student));

            return [
                'student' => $student,
                'checklist' => $checklist,
                'needed' => $checklist->pluck('costume'),
                'missing' => $missingList->pluck('costume'),
                // piem. "Vainags, Veste ×2" – cilvēkam salasāms trūkstošo saraksts
                'missingText' => $missingList
                    ->map(fn ($c) => $c['costume']->name.($c['required'] - $c['held'] > 1 ? ' ×'.($c['required'] - $c['held']) : ''))
                    ->implode(', '),
                'no_set' => $noSet,
                'ready' => ! $noSet && $missingList->isEmpty(),
            ];
        });

        $total = $rows->count();
        $ready = $rows->where('ready', true)->count();

        return [
            'students' => $rows,
            'ready' => $ready,
            'total' => $total,
            'percent' => $total > 0 ? (int) round($ready / $total * 100) : 0,
            'absentCount' => $data['absentCount'],
            'hasCostumes' => $data['costumes']->isNotEmpty(),
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
