@props([
    'upcoming' => collect(),
    'past' => collect(),
    'canManage' => false,
    'manageUrl' => null,
])

@php
    // apvienotais saraksts detalizētā skata blokiem uznirstošajā logā
    $all = $upcoming->concat($past);
    $hero = $upcoming->first();
    $next = $upcoming->slice(1, 2);

    // cik dienu līdz tuvākajam koncertam, cilvēkam saprotamā formā
    $heroWhen = null;
    if ($hero) {
        $days = now()->startOfDay()->diffInDays($hero->starts_at->copy()->startOfDay());
        $heroWhen = match (true) {
            $days === 0 => 'Today',
            $days === 1 => 'Tomorrow',
            default => "in {$days} days",
        };
    }
@endphp

<div x-data="{ tab: 'upcoming', selected: null }" class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
    <div class="mb-4 flex items-center justify-between gap-3">
        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Concerts</h3>
        @if($canManage && $manageUrl)
            <a href="{{ $manageUrl }}" class="text-xs font-semibold text-brand-accent hover:underline dark:text-brand-light">
                Manage concerts
            </a>
        @endif
    </div>

    @if($upcoming->isEmpty())
        <p class="rounded-lg border border-dashed border-gray-300 px-4 py-8 text-center text-sm text-gray-500 dark:border-gray-600 dark:text-gray-400">
            No upcoming concerts scheduled.
        </p>
    @else
        {{-- tuvākais koncerts – izcelts --}}
        <button type="button"
            x-on:click="selected = {{ $hero->id }}; $dispatch('open-modal', 'events-all')"
            class="block w-full rounded-2xl border border-brand-primary/25 bg-gradient-to-r from-brand-light/50 via-white to-brand-light/25 p-5 text-left shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-brand-light/20 dark:from-darkbrand-light/40 dark:via-gray-800 dark:to-darkbrand-light/20">
            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-wide text-brand-secondary dark:text-brand-light">
                    {{ $heroWhen }} · {{ $hero->starts_at->format('D, d.m.Y') }}
                    @if(!$canManage && $hero->group)
                        <span class="font-normal normal-case text-gray-400">· {{ $hero->group->name }}</span>
                    @endif
                </p>
                <p class="mt-1 truncate text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $hero->title }}</p>
                @if($hero->location)
                    <p class="mt-0.5 text-sm text-gray-600 dark:text-gray-300">📍 {{ $hero->location }}</p>
                @endif
            </div>
        </button>

        {{-- nākamie divi koncerti – šaurāka rinda --}}
        @if($next->isNotEmpty())
            <div class="mt-3 grid gap-2 sm:grid-cols-2">
                @foreach($next as $ev)
                    <button type="button"
                        x-on:click="selected = {{ $ev->id }}; $dispatch('open-modal', 'events-all')"
                        class="flex items-center justify-between gap-3 rounded-lg border border-gray-200 bg-white px-3.5 py-2.5 text-left text-sm transition hover:border-brand-primary/30 hover:bg-brand-light/20 dark:border-gray-700 dark:bg-gray-800 dark:hover:bg-darkbrand-light/20">
                        <span class="min-w-0 truncate font-medium text-gray-800 dark:text-gray-100">
                            {{ $ev->title }}
                            @if(!$canManage && $ev->group)
                                <span class="text-xs font-normal text-gray-400">· {{ $ev->group->name }}</span>
                            @endif
                        </span>
                        <span class="shrink-0 text-xs text-gray-500 dark:text-gray-400">{{ $ev->starts_at->format('d.m') }}</span>
                    </button>
                @endforeach
            </div>
        @endif

        @if($upcoming->count() > 3 || $past->isNotEmpty())
            <button type="button"
                x-on:click="selected = null; tab = 'upcoming'; $dispatch('open-modal', 'events-all')"
                class="mt-3 text-xs font-semibold text-brand-accent hover:underline dark:text-brand-light">
                Show more
            </button>
        @endif
    @endif

    {{-- uznirstošais logs: pilns saraksts vai viena koncerta detaļas --}}
    <x-modal name="events-all" maxWidth="lg">
        <div class="p-6">
            {{-- saraksta skats --}}
            <div x-show="!selected">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Concerts</h3>
                    <button type="button" x-on:click="$dispatch('close-modal', 'events-all')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">✕</button>
                </div>

                <div class="mb-4 flex gap-2 border-b border-gray-200 dark:border-gray-700">
                    <button type="button" x-on:click="tab = 'upcoming'"
                        :class="tab === 'upcoming' ? 'border-brand-primary text-brand-accent dark:text-brand-light' : 'border-transparent text-gray-500 dark:text-gray-400'"
                        class="border-b-2 px-3 pb-2 text-sm font-semibold">Upcoming ({{ $upcoming->count() }})</button>
                    <button type="button" x-on:click="tab = 'past'"
                        :class="tab === 'past' ? 'border-brand-primary text-brand-accent dark:text-brand-light' : 'border-transparent text-gray-500 dark:text-gray-400'"
                        class="border-b-2 px-3 pb-2 text-sm font-semibold">Past ({{ $past->count() }})</button>
                </div>

                <div class="max-h-96 space-y-2 overflow-y-auto pr-1">
                    <div x-show="tab === 'upcoming'" class="space-y-2">
                        @forelse($upcoming as $ev)
                            <button type="button" x-on:click="selected = {{ $ev->id }}"
                                class="flex w-full items-center justify-between gap-3 rounded-lg border border-gray-200 px-3.5 py-2.5 text-left text-sm hover:bg-brand-light/20 dark:border-gray-700 dark:hover:bg-darkbrand-light/20">
                                <span class="min-w-0 truncate font-medium text-gray-800 dark:text-gray-100">
                                    {{ $ev->title }}
                                    @if(!$canManage && $ev->group)
                                        <span class="text-xs font-normal text-gray-400">· {{ $ev->group->name }}</span>
                                    @endif
                                </span>
                                <span class="shrink-0 text-xs text-gray-500 dark:text-gray-400">{{ $ev->starts_at->format('d.m.Y') }}</span>
                            </button>
                        @empty
                            <p class="py-6 text-center text-sm text-gray-500 dark:text-gray-400">No upcoming concerts.</p>
                        @endforelse
                    </div>
                    <div x-show="tab === 'past'" class="space-y-2">
                        @forelse($past as $ev)
                            <button type="button" x-on:click="selected = {{ $ev->id }}"
                                class="flex w-full items-center justify-between gap-3 rounded-lg border border-gray-200 px-3.5 py-2.5 text-left text-sm hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-700/40">
                                <span class="min-w-0 truncate font-medium text-gray-600 dark:text-gray-300">
                                    {{ $ev->title }}
                                    @if(!$canManage && $ev->group)
                                        <span class="text-xs font-normal text-gray-400">· {{ $ev->group->name }}</span>
                                    @endif
                                </span>
                                <span class="shrink-0 text-xs text-gray-400">{{ $ev->starts_at->format('d.m.Y') }}</span>
                            </button>
                        @empty
                            <p class="py-6 text-center text-sm text-gray-500 dark:text-gray-400">No past concerts yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- detalizētais skats vienam koncertam --}}
            @foreach($all as $ev)
                <div x-show="selected === {{ $ev->id }}" style="display: none;">
                    <button type="button" x-on:click="selected = null" class="mb-4 text-xs font-semibold text-brand-accent hover:underline dark:text-brand-light">
                        ← Back to list
                    </button>

                    <p class="text-xs font-semibold uppercase tracking-wide text-brand-secondary dark:text-brand-light">{{ $ev->starts_at->format('l, d.m.Y · H:i') }}</p>
                    <h3 class="mt-1 text-xl font-semibold text-gray-900 dark:text-gray-100">{{ $ev->title }}</h3>

                    @if(!$canManage && $ev->group)
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $ev->group->name }}</p>
                    @endif

                    @if($ev->location)
                        <p class="mt-3 text-sm text-gray-700 dark:text-gray-200">📍 {{ $ev->location }}</p>
                    @endif

                    @if($ev->notes)
                        <p class="mt-3 whitespace-pre-line text-sm text-gray-600 dark:text-gray-300">{{ $ev->notes }}</p>
                    @endif

                    @if($ev->costumes->isNotEmpty())
                        <div class="mt-4">
                            <p class="mb-1.5 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Costumes needed</p>

                            @if($canManage)
                                {{-- skolotājam: dzīvā gatavības statistika, balstīta uz jau izsniegtajiem tērpiem --}}
                                <div class="space-y-2.5">
                                    @foreach($ev->costumeReadiness() as $row)
                                        <div>
                                            <div class="mb-1 flex items-center justify-between text-xs">
                                                <span class="font-medium text-gray-700 dark:text-gray-200">
                                                    {{ $row['costume']->name }}
                                                    @if($row['costume']->pivot->note) <span class="text-gray-400">· {{ $row['costume']->pivot->note }}</span> @endif
                                                </span>
                                                <span class="text-gray-500 dark:text-gray-400">{{ $row['assigned'] }}/{{ $row['target'] }} ready</span>
                                            </div>
                                            <div class="h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700">
                                                <div class="h-full rounded-full bg-brand-primary dark:bg-brand-secondary" style="width: {{ $row['percent'] }}%"></div>
                                            </div>
                                            @if($row['shortfall'] > 0)
                                                {{-- inventārā vispār nav tik daudz vienību, cik norādīts kā vajadzīgs --}}
                                                <p class="mt-1 text-xs font-medium text-amber-600 dark:text-amber-400">
                                                    ⚠ Only {{ $row['total'] }} in inventory — {{ $row['shortfall'] }} short of the {{ $row['target'] }} needed
                                                </p>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach($ev->costumes as $costume)
                                        <span class="rounded-full border border-brand-primary/25 bg-brand-light/40 px-2.5 py-1 text-xs font-medium text-brand-accent dark:border-brand-light/25 dark:bg-darkbrand-light/30 dark:text-brand-light">
                                            {{ $costume->name }}@if($costume->pivot->note) · {{ $costume->pivot->note }} @endif
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endif

                    @if($canManage)
                        <div class="mt-6 flex gap-2 border-t border-gray-100 pt-4 dark:border-gray-700">
                            <a href="{{ route('admin.events.edit', $ev) }}" class="inline-flex items-center rounded-lg border border-brand-primary/25 bg-brand-light/50 px-3 py-1.5 text-xs font-semibold text-brand-accent hover:bg-brand-light/75 dark:border-brand-secondary/35 dark:bg-darkbrand-light/45 dark:text-brand-light">
                                Edit
                            </a>
                            <form method="POST" action="{{ route('admin.events.destroy', $ev) }}" onsubmit="return confirm('Delete “{{ $ev->title }}”? This cannot be undone.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="inline-flex items-center rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100 dark:border-red-900 dark:bg-red-950/40 dark:text-red-300">
                                    Delete
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </x-modal>
</div>
