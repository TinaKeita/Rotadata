{{-- koncerta forma – kopīga izveidei un rediģēšanai --}}
@php
    $event = $event ?? null;
    $selectedCostumeIds = old('costume_ids', $event?->costumes->pluck('id')->all() ?? []);
    $costumeNotes = old('costume_notes', $event ? $event->costumes->pluck('pivot.note', 'id')->all() : []);

    // "min" datumam laukā rādām tikai tad, ja koncerts jau nav pagātnē — citādi nevarētu saglabāt
    // veca koncerta piezīmes, nemainot tā datumu
    $minStartsAt = (! $event || $event->starts_at->gte(now())) ? now()->format('Y-m-d\TH:i') : null;

    // pēc noklusējuma piedalās visi; rediģējot atzīmēti visi, izņemot saglabātos neapmeklētājus
    $absentIds = $event ? $event->absentees->pluck('id')->all() : [];
    $attendingIds = array_map('intval', old('attending_ids', $members->pluck('id')->reject(fn ($id) => in_array($id, $absentIds))->all()));
    $setNames = $sets->pluck('name', 'id');

    // papildu tērpi konkrētiem studentiem (piem. solistam) – formas rindas Alpine sarakstam
    $extraRows = old('extras', $event
        ? $event->studentCostumes->map(fn ($x) => ['user_id' => $x->user_id, 'costume_id' => $x->costume_id, 'quantity' => $x->quantity])->values()->all()
        : []);
@endphp

<div>
    <label for="title" class="ui-label">Title</label>
    <input type="text" name="title" id="title" value="{{ old('title', $event?->title) }}"
        class="mt-1.5 w-full rounded-lg border-line-strong px-3 py-2.5 text-ink focus:border-brand focus:ring-brand/30 bg-paper placeholder:text-ink-soft"
        required autofocus placeholder="Spring concert">
</div>

<div class="grid gap-5 sm:grid-cols-2">
    <div>
        <label for="starts_at" class="ui-label">Date &amp; time</label>
        <input type="datetime-local" name="starts_at" id="starts_at"
            value="{{ old('starts_at', $event?->starts_at?->format('Y-m-d\TH:i')) }}"
            @if($minStartsAt) min="{{ $minStartsAt }}" @endif
            class="mt-1.5 w-full rounded-lg border-line-strong px-3 py-2.5 text-ink focus:border-brand focus:ring-brand/30 bg-paper placeholder:text-ink-soft"
            required>
    </div>
    <div>
        <label for="location" class="ui-label">Location <span class="font-normal text-ink-soft">(optional)</span></label>
        <input type="text" name="location" id="location" value="{{ old('location', $event?->location) }}"
            class="mt-1.5 w-full rounded-lg border-line-strong px-3 py-2.5 text-ink focus:border-brand focus:ring-brand/30 bg-paper placeholder:text-ink-soft"
            placeholder="City concert hall">
    </div>
</div>

<div>
    <label for="notes" class="ui-label">Notes <span class="font-normal text-ink-soft">(optional)</span></label>
    <textarea name="notes" id="notes" rows="3"
        class="mt-1.5 w-full rounded-lg border-line-strong px-3 py-2.5 text-ink focus:border-brand focus:ring-brand/30 bg-paper placeholder:text-ink-soft"
        placeholder="Arrival time, dress code, anything students should know">{{ old('notes', $event?->notes) }}</textarea>
</div>

<div>
    <p class="ui-label">Costumes needed <span class="font-normal text-ink-soft">(optional, can be added later)</span></p>
    <p class="ui-help mt-0.5">
        How many are needed is counted for you: shared costumes by everyone performing, set costumes by the students in that set.
        Readiness then follows automatically from who has checked one out.
    </p>
    @if($costumes->isEmpty())
        <p class="mt-1.5 text-sm text-ink-soft">You don't have any costumes yet — you can attach them once you've added some.</p>
    @else
        <div class="mt-2 space-y-2">
            @foreach($costumes as $costume)
                <label class="flex items-start gap-3 rounded-lg border border-line px-3.5 py-2.5">
                    <input type="checkbox" name="costume_ids[]" value="{{ $costume->id }}"
                        {{ in_array($costume->id, $selectedCostumeIds) ? 'checked' : '' }}
                        class="mt-0.5 rounded border-line-strong text-brand focus:ring-brand/30">
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm font-medium text-ink">
                            {{ $costume->name }}
                            <span class="font-normal text-ink-soft">· {{ $costume->quantity }} in stock · {{ $costume->costumeSet?->name ? $costume->costumeSet->name.' only' : 'shared' }}</span>
                        </span>
                        <div class="mt-1 flex flex-wrap gap-2">
                            <input type="text" name="costume_notes[{{ $costume->id }}]" value="{{ $costumeNotes[$costume->id] ?? '' }}"
                                placeholder="Note (optional), e.g. bring by 17:00"
                                class="min-w-0 flex-1 rounded-md border-line-strong px-2.5 py-1.5 text-xs text-ink-muted focus:border-brand focus:ring-brand/30 bg-paper placeholder:text-ink-soft">
                        </div>
                    </span>
                </label>
            @endforeach
        </div>
    @endif
</div>

{{-- kas koncertā piedalās – neatzīmētie netiek skaitīti gatavībā --}}
<div x-data>
    <input type="hidden" name="attendance_sent" value="1">
    <div class="flex flex-wrap items-baseline justify-between gap-2">
        <p class="ui-label">Who's performing</p>
        @if($members->isNotEmpty())
            <span class="flex gap-3 text-xs font-semibold text-brand">
                <button type="button" x-on:click="$root.querySelectorAll('input[name^=attending_ids]').forEach(cb => cb.checked = true)" class="hover:underline">Tick all</button>
                <button type="button" x-on:click="$root.querySelectorAll('input[name^=attending_ids]').forEach(cb => cb.checked = false)" class="hover:underline">Untick all</button>
            </span>
        @endif
    </div>
    <p class="ui-help mt-0.5">
        Everyone takes part by default. Untick students who won't be at this concert, so readiness doesn't count them.
    </p>
    @if($members->isEmpty())
        <p class="mt-1.5 text-sm text-ink-soft">Your group has no students yet.</p>
    @else
        @php
            // viena kolonna katram komplektam (piem. meitenes / puiši), bez komplekta – atsevišķi beigās
            $columns = $sets->map(fn ($set) => [
                'title' => $set->name,
                'members' => $members->filter(fn ($m) => (int) $m->pivot->costume_set_id === $set->id)->values(),
            ])->filter(fn ($c) => $c['members']->isNotEmpty())->values();

            $noSet = $members->filter(fn ($m) => ! $setNames->has($m->pivot->costume_set_id))->values();
            if ($noSet->isNotEmpty()) {
                $columns->push(['title' => $columns->isEmpty() ? 'Students' : 'No set', 'members' => $noSet]);
            }

            $colClass = match (min($columns->count(), 3)) {
                1 => 'sm:grid-cols-1',
                2 => 'sm:grid-cols-2',
                default => 'sm:grid-cols-3',
            };
        @endphp

        <div class="mt-2 grid items-start gap-4 {{ $colClass }}">
            @foreach($columns as $column)
                <div x-data class="min-w-0">
                    <div class="mb-1.5 flex items-baseline justify-between gap-2">
                        <p class="ui-eyebrow">{{ $column['title'] }} · {{ $column['members']->count() }}</p>
                        <span class="flex gap-2 text-xs font-medium text-brand">
                            <button type="button" x-on:click="$root.querySelectorAll('input[name^=attending_ids]').forEach(cb => cb.checked = true)" class="hover:underline">All</button>
                            <button type="button" x-on:click="$root.querySelectorAll('input[name^=attending_ids]').forEach(cb => cb.checked = false)" class="hover:underline">None</button>
                        </span>
                    </div>
                    <div class="space-y-1.5">
                        @foreach($column['members'] as $member)
                            <label class="flex items-center gap-2.5 rounded-lg border border-line px-3 py-2 text-sm">
                                <input type="checkbox" name="attending_ids[]" value="{{ $member->id }}"
                                    @checked(in_array($member->id, $attendingIds, true))
                                    class="rounded border-line-strong text-brand focus:ring-brand/30">
                                <span class="min-w-0 flex-1 truncate text-ink">{{ $member->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

{{-- papildu tērpi konkrētiem studentiem (piem. solistam vēl viens tērps) – nāk klāt tam, ko prasa komplekts --}}
<div x-data="{ rows: @js($extraRows) }">
    <p class="ui-label">Extra costumes for specific students <span class="font-normal text-ink-soft">(optional)</span></p>
    <p class="ui-help mt-0.5">
        For example a soloist who needs one more costume than the rest. It's added on top of what their set needs,
        and they only count as ready once they have it too. Choosing a costume they already need raises how many of it they need.
    </p>

    @if($members->isEmpty() || $costumes->isEmpty())
        <p class="mt-1.5 text-sm text-ink-soft">You need students and costumes in your group first.</p>
    @else
        <div class="mt-2 space-y-2">
            <template x-for="(row, index) in rows" :key="index">
                <div class="flex flex-wrap items-center gap-2 rounded-lg border border-line px-3 py-2">
                    <select :name="`extras[${index}][user_id]`" x-model="row.user_id" required aria-label="Student"
                        class="min-w-0 flex-1 rounded-lg border-line-strong bg-paper py-1.5 text-sm text-ink focus:border-brand focus:ring-brand/20">
                        <option value="">Student…</option>
                        @foreach($members as $member)
                            <option value="{{ $member->id }}">{{ $member->name }}</option>
                        @endforeach
                    </select>
                    <select :name="`extras[${index}][costume_id]`" x-model="row.costume_id" required aria-label="Costume"
                        class="min-w-0 flex-1 rounded-lg border-line-strong bg-paper py-1.5 text-sm text-ink focus:border-brand focus:ring-brand/20">
                        <option value="">Costume…</option>
                        @foreach($costumes as $costume)
                            <option value="{{ $costume->id }}">{{ $costume->name }}</option>
                        @endforeach
                    </select>
                    <input type="number" :name="`extras[${index}][quantity]`" x-model="row.quantity" min="1" max="20" required aria-label="How many"
                        class="w-20 rounded-lg border-line-strong bg-paper py-1.5 text-sm text-ink focus:border-brand focus:ring-brand/20">
                    <button type="button" x-on:click="rows.splice(index, 1)" class="ui-btn-danger ui-btn-sm">Remove</button>
                </div>
            </template>
        </div>

        <button type="button" x-on:click="rows.push({ user_id: '', costume_id: '', quantity: 1 })" class="ui-btn-ghost ui-btn-sm mt-2">
            + Add extra costume
        </button>
    @endif
</div>
