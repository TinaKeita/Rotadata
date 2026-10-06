@props([
    'upcoming' => collect(),
    'past' => collect(),
    'canManage' => false,
    'manageUrl' => null,
    // true: rāda tikai uznirstošo logu (panelis zīmē savu koncerta kartīti un atver logu ar notikumu open-events)
    'modalOnly' => false,
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

<div x-data="{ tab: 'upcoming', selected: null }"
    {{-- ārējs atvēršanas notikums: detail.id atver konkrētu koncertu, detail.tab – sarakstu --}}
    x-on:open-events.window="selected = $event.detail?.id ?? null; tab = $event.detail?.tab ?? 'upcoming'; $dispatch('open-modal', 'events-all')"
    @class(['ui-card' => ! $modalOnly])>
    @unless($modalOnly)
    <div class="mb-4 flex items-center justify-between gap-3">
        <h3 class="ui-eyebrow">Next on the calendar</h3>
        @if($canManage && $manageUrl)
            <a href="{{ $manageUrl }}" class="ui-link text-[13.5px]">
                Manage concerts
            </a>
        @endif
    </div>

    @if($upcoming->isEmpty())
        <p class="ui-empty">
            No upcoming concerts scheduled.
        </p>
    @else
        {{-- tuvākais koncerts – izcelts --}}
        <button type="button"
            x-on:click="selected = {{ $hero->id }}; $dispatch('open-modal', 'events-all')"
            class="block w-full rounded-[14px] border border-line bg-paper p-5 text-left transition-colors hover:border-brand">
            <div class="min-w-0">
                <p class="font-mono text-[11.5px] uppercase tracking-[0.1em] text-brand">
                    {{ $heroWhen }} · {{ $hero->starts_at->format('D, d.m.Y') }}
                    @if(!$canManage && $hero->group)
                        <span class="font-normal normal-case text-ink-soft">· {{ $hero->group->name }}</span>
                    @endif
                </p>
                <p class="mt-1.5 line-clamp-2 font-display text-[28px] leading-tight tracking-[-0.01em] text-ink">{{ $hero->title }}</p>
                @if(!$canManage && $hero->isAbsent(auth()->user()))
                    <span class="ui-chip mt-2">Not performing</span>
                @endif
                @if($hero->location)
                    <p class="mt-1 font-mono text-[12.5px] text-ink-soft">{{ $hero->starts_at->format('H:i') }} · {{ $hero->location }}</p>
                @endif
            </div>
        </button>

        {{-- nākamie divi koncerti – šaurāka rinda --}}
        @if($next->isNotEmpty())
            <div class="mt-3 grid gap-2 sm:grid-cols-2">
                @foreach($next as $ev)
                    <button type="button"
                        x-on:click="selected = {{ $ev->id }}; $dispatch('open-modal', 'events-all')"
                        class="flex w-full min-w-0 items-center justify-between gap-3 rounded-lg border border-line bg-surface px-3.5 py-2.5 text-left text-[15px] transition-colors hover:border-brand hover:text-brand">
                        <span class="min-w-0">
                            <span class="block truncate font-medium text-ink">{{ $ev->title }}</span>
                            @if(!$canManage && $ev->group)
                                <span class="block truncate text-xs font-normal text-ink-soft">{{ $ev->group->name }}</span>
                            @endif
                        </span>
                        <span class="shrink-0 font-mono text-[12px] text-ink-soft">
                            @if(!$canManage && $ev->isAbsent(auth()->user())) not performing · @endif{{ $ev->starts_at->format('d.m') }}
                        </span>
                    </button>
                @endforeach
            </div>
        @endif

        @if($upcoming->count() > 3 || $past->isNotEmpty())
            <button type="button"
                x-on:click="selected = null; tab = 'upcoming'; $dispatch('open-modal', 'events-all')"
                class="ui-link mt-3 text-[13.5px]">
                Show all and past
            </button>
        @endif
    @endif
    @endunless

    {{-- uznirstošais logs: pilns saraksts vai viena koncerta detaļas --}}
    <x-modal name="events-all" maxWidth="lg">
        <div class="p-6">
            {{-- saraksta skats --}}
            <div x-show="!selected">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="ui-heading">Concerts</h3>
                    <button type="button" x-on:click="$dispatch('close-modal', 'events-all')" class="text-ink-soft hover:text-ink-muted">✕</button>
                </div>

                <div class="mb-4 flex gap-2 border-b border-line">
                    <button type="button" x-on:click="tab = 'upcoming'"
                        :class="tab === 'upcoming' ? 'border-brand text-brand' : 'border-transparent text-ink-soft'"
                        class="-mb-px border-b-2 px-3 pb-2 text-sm font-medium">Upcoming ({{ $upcoming->count() }})</button>
                    <button type="button" x-on:click="tab = 'past'"
                        :class="tab === 'past' ? 'border-brand text-brand' : 'border-transparent text-ink-soft'"
                        class="-mb-px border-b-2 px-3 pb-2 text-sm font-medium">Past ({{ $past->count() }})</button>
                </div>

                <div class="max-h-96 space-y-2 overflow-y-auto pr-1">
                    <div x-show="tab === 'upcoming'" class="space-y-2">
                        @forelse($upcoming as $ev)
                            <button type="button" x-on:click="selected = {{ $ev->id }}"
                                class="flex w-full items-center justify-between gap-3 rounded-lg border border-line px-3.5 py-2.5 text-left text-[15px] transition-colors hover:border-brand hover:text-brand">
                                <span class="min-w-0">
                                    <span class="block truncate font-medium text-ink">{{ $ev->title }}</span>
                                    @if(!$canManage && $ev->group)
                                        <span class="block truncate text-xs font-normal text-ink-soft">{{ $ev->group->name }}</span>
                                    @endif
                                </span>
                                <span class="shrink-0 font-mono text-[12px] text-ink-soft">{{ $ev->starts_at->format('d.m.Y') }}</span>
                            </button>
                        @empty
                            <p class="py-6 text-center text-sm text-ink-soft">No upcoming concerts.</p>
                        @endforelse
                    </div>
                    <div x-show="tab === 'past'" class="space-y-2">
                        @forelse($past as $ev)
                            <button type="button" x-on:click="selected = {{ $ev->id }}"
                                class="flex w-full items-center justify-between gap-3 rounded-lg border border-line px-3.5 py-2.5 text-left text-[15px] transition-colors hover:border-brand hover:text-brand">
                                <span class="min-w-0">
                                    <span class="block truncate font-medium text-ink-muted">{{ $ev->title }}</span>
                                    @if(!$canManage && $ev->group)
                                        <span class="block truncate text-xs font-normal text-ink-soft">{{ $ev->group->name }}</span>
                                    @endif
                                </span>
                                <span class="shrink-0 font-mono text-[12px] text-ink-soft">{{ $ev->starts_at->format('d.m.Y') }}</span>
                            </button>
                        @empty
                            <p class="py-6 text-center text-sm text-ink-soft">No past concerts yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- detalizētais skats vienam koncertam --}}
            @foreach($all as $ev)
                <div x-show="selected === {{ $ev->id }}" style="display: none;">
                    <button type="button" x-on:click="selected = null" class="ui-back mb-4">
                        ← Back to list
                    </button>

                    <p class="font-mono text-[11.5px] uppercase tracking-[0.1em] text-brand">{{ $ev->starts_at->format('l, d.m.Y · H:i') }}</p>
                    <h3 class="ui-heading text-[26px] mt-1">{{ $ev->title }}</h3>

                    @if(!$canManage && $ev->group)
                        <p class="mt-1 text-sm text-ink-soft">{{ $ev->group->name }}</p>
                    @endif

                    @if($ev->location)
                        <p class="mt-2 font-mono text-[12.5px] text-ink-soft">{{ $ev->location }}</p>
                    @endif

                    @if($ev->notes)
                        <p class="mt-3 whitespace-pre-line text-sm text-ink-muted">{{ $ev->notes }}</p>
                    @endif

                    @if($ev->costumes->isNotEmpty())
                        <div class="mt-4">
                            <p class="ui-eyebrow mb-2">Costumes needed</p>

                            @if($canManage)
                                {{-- skolotājam: dzīvā gatavības statistika, balstīta uz jau izsniegtajiem tērpiem --}}
                                <div class="space-y-2.5">
                                    @foreach($ev->costumeReadiness() as $row)
                                        <div>
                                            <div class="mb-1 flex items-center justify-between text-xs">
                                                <span class="font-medium text-ink-muted">
                                                    {{ $row['costume']->name }}
                                                    @if($row['costume']->pivot?->note) <span class="text-ink-soft">· {{ $row['costume']->pivot->note }}</span> @endif
                                                    @if($row['extra_only']) <span class="text-ink-soft">· extra only</span> @endif
                                                </span>
                                                <span class="font-mono text-ink-soft">{{ $row['assigned'] }}/{{ $row['target'] }} ready</span>
                                            </div>
                                            <div class="h-1.5 overflow-hidden rounded-full bg-line">
                                                <div class="h-full rounded-full bg-brand" style="width: {{ $row['percent'] }}%"></div>
                                            </div>
                                            @if($row['shortfall'] > 0)
                                                {{-- inventārā vispār nav tik daudz vienību, cik norādīts kā vajadzīgs --}}
                                                <p class="mt-1 text-xs font-medium text-rust">
                                                    ⚠ Only {{ $row['total'] }} in inventory — {{ $row['shortfall'] }} short of the {{ $row['target'] }} needed
                                                </p>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                {{-- studentam: tikai viņa komplekta un kopīgie tērpi, atzīmējot, kas jau ir rokās --}}
                                @php $mine = $ev->readinessFor(auth()->user()); @endphp
                                @if(! $mine)
                                    <p class="ui-alert ui-alert-warn">You're not performing in this concert, so you don't need costumes for it.</p>
                                @elseif($mine['no_set'])
                                    <p class="ui-alert ui-alert-warn">Your teacher hasn't put you in a set yet, so it isn't clear which costumes you need.</p>
                                @else
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach($mine['checklist'] as $c)
                                            @php $done = $c['held'] >= $c['required']; @endphp
                                            <span class="ui-chip {{ $done ? 'ui-chip-good' : 'ui-chip-late' }}">
                                                {{ $done ? '✓' : '○' }} {{ $c['costume']->name }}@if($c['required'] > 1) {{ $c['held'] }}/{{ $c['required'] }}@endif @if($c['extra'])· extra @endif @if($c['costume']->pivot?->note)· {{ $c['costume']->pivot->note }} @endif
                                            </span>
                                        @endforeach
                                    </div>
                                    <p class="ui-help mt-2">✓ you have it · ○ still to take</p>
                                @endif
                            @endif
                        </div>
                    @endif

                    {{-- notikušus koncertus vairs nevar mainīt vai dzēst, tāpēc pogas tiem nerāda --}}
                    @if($canManage && ! $ev->isPast())
                        <div class="mt-6 flex gap-2 border-t border-line-soft pt-4">
                            <a href="{{ route('admin.events.edit', $ev) }}" class="ui-btn-ghost ui-btn-sm">
                                Edit
                            </a>
                            <form method="POST" action="{{ route('admin.events.destroy', $ev) }}" onsubmit="return confirm('Delete “{{ $ev->title }}”? This cannot be undone.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="ui-btn-danger ui-btn-sm">
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
