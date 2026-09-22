{{-- koncerta forma – kopīga izveidei un rediģēšanai --}}
@php
    $event = $event ?? null;
    $selectedCostumeIds = old('costume_ids', $event?->costumes->pluck('id')->all() ?? []);
    $costumeNotes = old('costume_notes', $event ? $event->costumes->pluck('pivot.note', 'id')->all() : []);
    $costumeTargets = old('costume_targets', $event ? $event->costumes->pluck('pivot.target_count', 'id')->all() : []);

    // "min" datumam laukā rādām tikai tad, ja koncerts jau nav pagātnē — citādi nevarētu saglabāt
    // veca koncerta piezīmes, nemainot tā datumu
    $minStartsAt = (! $event || $event->starts_at->gte(now())) ? now()->format('Y-m-d\TH:i') : null;
@endphp

<div>
    <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Title</label>
    <input type="text" name="title" id="title" value="{{ old('title', $event?->title) }}"
        class="mt-1.5 w-full rounded-lg border-gray-300 px-3 py-2.5 text-gray-800 shadow-sm focus:border-brand-primary focus:ring-brand-secondary/50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
        required autofocus placeholder="Spring concert">
</div>

<div class="grid gap-5 sm:grid-cols-2">
    <div>
        <label for="starts_at" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Date &amp; time</label>
        <input type="datetime-local" name="starts_at" id="starts_at"
            value="{{ old('starts_at', $event?->starts_at?->format('Y-m-d\TH:i')) }}"
            @if($minStartsAt) min="{{ $minStartsAt }}" @endif
            class="mt-1.5 w-full rounded-lg border-gray-300 px-3 py-2.5 text-gray-800 shadow-sm focus:border-brand-primary focus:ring-brand-secondary/50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
            required>
    </div>
    <div>
        <label for="location" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Location <span class="font-normal text-gray-400">(optional)</span></label>
        <input type="text" name="location" id="location" value="{{ old('location', $event?->location) }}"
            class="mt-1.5 w-full rounded-lg border-gray-300 px-3 py-2.5 text-gray-800 shadow-sm focus:border-brand-primary focus:ring-brand-secondary/50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
            placeholder="City concert hall">
    </div>
</div>

<div>
    <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Notes <span class="font-normal text-gray-400">(optional)</span></label>
    <textarea name="notes" id="notes" rows="3"
        class="mt-1.5 w-full rounded-lg border-gray-300 px-3 py-2.5 text-gray-800 shadow-sm focus:border-brand-primary focus:ring-brand-secondary/50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
        placeholder="Arrival time, dress code, anything students should know">{{ old('notes', $event?->notes) }}</textarea>
</div>

<div>
    <p class="block text-sm font-medium text-gray-700 dark:text-gray-200">Costumes needed <span class="font-normal text-gray-400">(optional, can be added later)</span></p>
    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
        Leave "Needed" blank if everyone in the group needs it — readiness is then tracked automatically from who has already checked one out.
    </p>
    @if($costumes->isEmpty())
        <p class="mt-1.5 text-sm text-gray-500 dark:text-gray-400">You don't have any costumes yet — you can attach them once you've added some.</p>
    @else
        <div class="mt-2 space-y-2">
            @foreach($costumes as $costume)
                <label class="flex items-start gap-3 rounded-lg border border-gray-200 px-3.5 py-2.5 dark:border-gray-700">
                    <input type="checkbox" name="costume_ids[]" value="{{ $costume->id }}"
                        {{ in_array($costume->id, $selectedCostumeIds) ? 'checked' : '' }}
                        class="mt-0.5 rounded border-gray-300 text-brand-primary focus:ring-brand-secondary/50">
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm font-medium text-gray-800 dark:text-gray-100">
                            {{ $costume->name }}
                            <span class="font-normal text-gray-400">· {{ $costume->quantity }} in stock</span>
                        </span>
                        <div class="mt-1 flex flex-wrap gap-2">
                            <input type="text" name="costume_notes[{{ $costume->id }}]" value="{{ $costumeNotes[$costume->id] ?? '' }}"
                                placeholder="Note (optional), e.g. bring by 17:00"
                                class="min-w-0 flex-1 rounded-md border-gray-300 px-2.5 py-1.5 text-xs text-gray-700 shadow-sm focus:border-brand-primary focus:ring-brand-secondary/50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                            <input type="number" name="costume_targets[{{ $costume->id }}]" value="{{ $costumeTargets[$costume->id] ?? '' }}"
                                placeholder="Needed (default {{ $memberCount }})" min="1" max="1000"
                                class="w-40 shrink-0 rounded-md border-gray-300 px-2.5 py-1.5 text-xs text-gray-700 shadow-sm focus:border-brand-primary focus:ring-brand-secondary/50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                        </div>
                        @if($costume->quantity > 0 && ($costumeTargets[$costume->id] ?? null) > $costume->quantity)
                            <p class="mt-1 text-xs font-medium text-amber-600 dark:text-amber-400">⚠ Only {{ $costume->quantity }} in stock — that's fewer than requested.</p>
                        @endif
                    </span>
                </label>
            @endforeach
        </div>
    @endif
</div>
