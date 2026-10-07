<x-app-layout>
    {{-- studenta sākumlapa: paša gatavība nākamajam koncertam, koncerti un grupas (inventārs, grupas pamešana) --}}
    @php
        $firstName = \Illuminate\Support\Str::before(trim(auth()->user()->name), ' ');

        // cik dienu līdz koncertam, cilvēkam saprotamā formā (tāpat kā skolotāja panelī)
        $whenLabel = function ($event) {
            $days = (int) now()->startOfDay()->diffInDays($event->starts_at->copy()->startOfDay());

            return match (true) {
                $days === 0 => 'Today',
                $days === 1 => 'Tomorrow',
                default => "in {$days} days",
            };
        };
    @endphp

    <x-slot name="header">
        <x-page-header :eyebrow="now()->format('l, j F')" :title="'Hello, '.$firstName.'.'"
            subtitle="Your next concert, the costumes you have and the groups you're in." />
    </x-slot>

    <div class="space-y-6">
        @include('partials.group-invitations')

        {{-- nākamais koncerts, kurā students piedalās: kas jau ir un kas vēl jāpaņem --}}
        @if($nextConcert)
            @php
                $event = $nextConcert['event'];
                $row = $nextConcert['row'];
            @endphp
            <section data-reveal="" data-reveal-delay="60" class="ui-card">
                <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                    <p class="ui-eyebrow">Before your next concert</p>
                    <span class="ui-chip ui-chip-good">{{ $whenLabel($event) }}</span>
                </div>

                <button type="button" x-data x-on:click="$dispatch('open-events', { id: {{ $event->id }} })" class="block text-left hover:text-brand">
                    <span class="block font-display text-[28px] leading-tight tracking-[-0.01em]">{{ $event->title }}</span>
                </button>
                <p class="mt-1 font-mono text-[12.5px] text-ink-soft">
                    {{ $event->starts_at->format('D, d.m.Y · H:i') }}@if($event->location) · {{ $event->location }}@endif · {{ $event->group->name }}
                </p>

                <div class="mt-5 rounded-[14px] border px-4 py-3 text-[15px] {{ $row['ready'] ? 'border-brand/25 bg-brand-tint text-brand' : 'border-rust/25 bg-rust-tint text-rust' }}">
                    @if($row['no_set'])
                        Your teacher hasn't put you in a set yet, so it isn't clear which costumes you need. Ask them to choose your set.
                    @elseif($row['ready'])
                        You have everything you need. ✓
                    @else
                        You still need: <strong class="font-semibold">{{ $row['missingText'] }}</strong>
                    @endif
                </div>

                @if($row['checklist']->isNotEmpty())
                    <div class="mt-4 flex flex-wrap gap-1.5">
                        @foreach($row['checklist'] as $c)
                            @php $done = $c['held'] >= $c['required']; @endphp
                            <span class="ui-chip {{ $done ? 'ui-chip-good' : 'ui-chip-late' }}">
                                {{ $done ? '✓' : '○' }} {{ $c['costume']->name }}@if($c['required'] > 1) {{ $c['held'] }}/{{ $c['required'] }}@endif @if($c['extra'])· extra @endif
                            </span>
                        @endforeach
                    </div>
                @endif
            </section>
        @endif

        <div data-reveal="" data-reveal-delay="100">
            <x-events.timeline :upcoming="$upcoming" :past="$past" />
        </div>

        <section data-reveal="" data-reveal-delay="140">
            <div class="mb-3 flex items-baseline justify-between gap-3">
                <h2 class="ui-heading">Your groups</h2>
                <span class="ui-eyebrow">{{ $groups->count() }} {{ Str::plural('group', $groups->count()) }}</span>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                @forelse($groups as $group)
                    @php $held = $itemsByGroup[$group->id] ?? collect(); @endphp
                    {{-- min-w-0: garš grupas nosaukums tiek saīsināts, nevis izstiepj kartīti platāku par ekrānu --}}
                    <article class="ui-card flex min-w-0 flex-col gap-5">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <h3 class="line-clamp-2 font-display text-[26px] font-normal leading-tight tracking-[-0.01em]">{{ $group->name }}</h3>
                                <p class="mt-1 font-mono text-[12px] text-ink-soft">Teacher · {{ $group->admin?->name ?? 'Not assigned' }}</p>
                            </div>
                            {{-- studenta komplekts šajā grupā --}}
                            <span class="ui-chip shrink-0 {{ $group->pivot->costume_set_id ? 'ui-chip-good' : '' }}">
                                {{ $setNames[$group->pivot->costume_set_id] ?? 'No set' }}
                            </span>
                        </div>

                        {{-- kas šobrīd rokās no šīs grupas --}}
                        <div>
                            <p class="ui-eyebrow mb-1.5">With you now · {{ $held->count() }}</p>
                            @forelse($held as $item)
                                <p class="flex items-baseline justify-between gap-3 border-t border-line-soft py-1.5 text-[14.5px] first-of-type:border-t-0">
                                    <span class="truncate">{{ $item->costume->name }}</span>
                                    <span class="shrink-0 font-mono text-[12px] text-ink-soft">{{ $item->code }}</span>
                                </p>
                            @empty
                                <p class="text-[14.5px] text-ink-muted">Nothing checked out from this group.</p>
                            @endforelse
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <a href="{{ route('members.costumes.index', $group->id ?? 0) }}" class="ui-btn">
                                My inventory
                            </a>

                            {{-- students pats pamet grupu – konts vienmēr paliek --}}
                            <form method="POST" action="{{ route('members.costumes.leave', $group) }}"
                                onsubmit="return confirm('Leave {{ $group->name }}? You can only do this once you\'ve returned every item from this group.');">
                                @csrf
                                <button type="submit" class="ui-btn-ghost">Leave group</button>
                            </form>
                        </div>
                    </article>
                @empty
                    <div class="ui-empty lg:col-span-2">
                        You're not part of any group yet. Ask your teacher to add you.
                    </div>
                @endforelse
            </div>
        </section>
    </div>
</x-app-layout>
