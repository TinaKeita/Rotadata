<x-app-layout>
    {{-- meklēšanas rezultāti: tērpu vienības ar statusu un vēsturi, studenti ar to, kas viņiem rokās --}}
    <x-slot name="header">
        <x-page-header :eyebrow="$group?->name" title="Search"
            subtitle="Find an item by its code (e.g. VAI-03) or costume name, or a student by name or email." />
    </x-slot>

    <form method="GET" action="{{ route('admin.search') }}" class="mb-8 flex flex-wrap items-center gap-2">
        <input type="search" name="q" value="{{ $q }}" autofocus placeholder="VAI-03, Vainags, Marta…" aria-label="Search"
            class="ui-input max-w-xl flex-1">
        <button type="submit" class="ui-btn">Search</button>
    </form>

    @if($q === '')
        <p class="ui-empty">Type an item code, a costume name or a student's name.</p>
    @elseif($items->isEmpty() && $students->isEmpty())
        <p class="ui-empty">Nothing found for “{{ $q }}”.</p>
    @else
        {{-- tērpu vienības --}}
        @if($items->isNotEmpty())
            <section class="mb-8">
                <h2 class="ui-eyebrow mb-3">Items · {{ $items->count() }}</h2>
                <div class="space-y-3">
                    @foreach($items as $item)
                        <article class="ui-card">
                            {{-- kods un statuss vienmēr augšā; kam izsniegts un "Take back" – atsevišķā rindā zem tā --}}
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="font-mono text-[15px] font-medium">{{ $item->code }}</p>
                                    <a href="{{ route('admin.costumes.show', $item->costume) }}" class="ui-link text-[14.5px]">{{ $item->costume->name }}</a>
                                    <span class="text-[13px] text-ink-soft">· {{ $item->costume->costumeSet?->name ?? 'shared' }}</span>
                                </div>
                                <span class="ui-chip {{ $item->assigned_to ? 'ui-chip-late' : 'ui-chip-good' }} shrink-0">{{ $item->assigned_to ? 'Taken' : 'Available' }}</span>
                            </div>

                            @if($item->assigned_to)
                                <div class="mt-3 flex flex-wrap items-center justify-between gap-x-4 gap-y-2">
                                    <p class="min-w-0 text-[14.5px]">
                                        by <a href="{{ route('admin.members.show', $item->user) }}" class="ui-link">{{ $item->user?->name ?? 'Unknown' }}</a>
                                        <span class="whitespace-nowrap font-mono text-[12px] text-ink-soft">since {{ $item->assigned_at?->format('d.m.Y') }}</span>
                                    </p>
                                    <form method="POST" action="{{ route('admin.costumes.items.unassign', $item) }}">
                                        @csrf
                                        <button type="submit" class="ui-btn-ghost ui-btn-sm">Take back</button>
                                    </form>
                                </div>
                            @endif

                            {{-- pilna vēsture; atvērta uzreiz, ja meklēja tieši šo kodu --}}
                            <details class="mt-3 border-t border-line-soft pt-3" @if($loop->first && $items->count() <= 3) open @endif>
                                <summary class="cursor-pointer text-[13px] font-medium text-brand">History ({{ $item->assignments->count() }})</summary>
                                @if($item->assignments->isEmpty())
                                    <p class="mt-2 text-[13.5px] text-ink-soft">Never taken.</p>
                                @else
                                    <ul class="mt-2 space-y-1.5">
                                        @foreach($item->assignments as $log)
                                            <li class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-0.5 text-[13.5px]">
                                                <span>
                                                    <span class="font-medium">{{ $log->user_name }}</span>
                                                    @if($log->assigned_by && $log->assigned_by !== $log->user_id)
                                                        <span class="text-ink-soft">(given by {{ $log->assignedBy->name ?? 'teacher' }})</span>
                                                    @endif
                                                </span>
                                                <span class="font-mono text-[12px] text-ink-soft">
                                                    {{ $log->assigned_at->format('d.m.Y') }} &rarr;
                                                    @if($log->returned_at)
                                                        {{ $log->returned_at->format('d.m.Y') }}
                                                        @if($log->return_note === 'admin')
                                                            (taken back by {{ $log->returnedBy->name ?? 'teacher' }})
                                                        @elseif($log->return_note === 'transfer')
                                                            (handed over to {{ $log->returnedBy->name ?? 'another member' }})
                                                        @elseif($log->return_note === 'removed')
                                                            (member removed)
                                                        @elseif($log->return_note === 'left_group')
                                                            (left the group)
                                                        @else
                                                            (returned)
                                                        @endif
                                                    @else
                                                        <span class="text-rust">still out</span>
                                                    @endif
                                                </span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </details>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- studenti --}}
        @if($students->isNotEmpty())
            <section>
                <h2 class="ui-eyebrow mb-3">Students · {{ $students->count() }}</h2>
                <div class="ui-card !py-1">
                    @foreach($students as $student)
                        <div class="ui-row flex-wrap">
                            <div class="min-w-0">
                                <a href="{{ route('admin.members.show', $student) }}" class="ui-link text-[15px]">{{ $student->name }}</a>
                                <p class="font-mono text-[12px] text-ink-soft">{{ $student->email }} · {{ $setNames[$student->pivot->costume_set_id] ?? 'No set' }}</p>
                            </div>
                            <p class="text-right text-[13.5px] text-ink-muted">
                                @forelse($student->assignedCostumeItems as $held)
                                    <span class="font-mono text-[12px]">{{ $held->code }}</span> {{ $held->costume->name }}@unless($loop->last), @endunless
                                @empty
                                    <span class="text-ink-soft">holds nothing</span>
                                @endforelse
                            </p>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif
    @endif
</x-app-layout>
