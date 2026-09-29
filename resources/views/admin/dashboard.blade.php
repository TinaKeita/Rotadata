<x-app-layout>
    {{-- skolotāja panelis kā darāmo darbu saraksts: "Needs you", tuvākais koncerts, kluss kopsavilkums.
         Statistika un žurnāls pārcelti uz admin.activity; koncertu saraksts, pagātne un rediģēšana – uznirstošajā logā --}}


    @php
        $firstName = \Illuminate\Support\Str::before(trim(auth()->user()->name), ' ');

        $lateChip = 'bg-rust-tint text-rust';
        $softChip = 'bg-surface-sunk text-ink-soft';
        $goodChip = 'bg-brand-tint text-brand';
        $pill = 'inline-flex items-center rounded-full border border-line-strong px-3.5 py-1.5 text-[13px] font-medium text-ink hover:border-brand hover:text-brand';
    @endphp

    <div>
        <div>

            @if(is_null($group))
                <p class="mb-2 font-mono text-[11.5px] uppercase tracking-[0.1em] text-ink-soft">{{ now()->format('l, j F') }}</p>
                <h1 class="mb-8 font-display text-[length:clamp(32px,4vw,44px)] font-normal leading-[1.08] tracking-[-0.02em]">Hello, {{ $firstName }}.</h1>

                {{-- paziņojumi (piem. students pametis grupu) paliek redzami arī bez grupas --}}
                @foreach($notifications as $n)
                    <div class="mb-3 flex flex-wrap items-center justify-between gap-3 rounded-[14px] border border-line bg-surface px-5 py-4 text-[15px]">
                        <span><strong class="font-semibold">{{ $n->data['student_name'] }}</strong> left {{ $n->data['group_name'] }}
                            <span class="font-mono text-[12px] text-ink-soft">· {{ $n->created_at->diffForHumans() }}</span></span>
                        <form method="POST" action="{{ route('admin.notifications.dismiss', $n->id) }}">
                            @csrf
                            <button type="submit" class="{{ $pill }}">Dismiss</button>
                        </form>
                    </div>
                @endforeach

                <div class="rounded-[14px] border border-dashed border-line-strong px-4 py-12 text-center text-[15px] text-ink-muted">
                    You don't manage a group yet, so there's nothing to show here.
                </div>
            @else
                @php
                    // cik "lietu" gaida skolotāju – katra rinda ir viena lieta
                    $needCount = count($needs);
                    $words = [1 => 'One thing', 'Two things', 'Three things', 'Four things', 'Five things', 'Six things', 'Seven things', 'Eight things', 'Nine things'];
                    $greeting = $needCount === 0
                        ? "All quiet, {$firstName}."
                        : ($words[$needCount] ?? "{$needCount} things").", {$firstName}.";

                    // īss paskaidrojums zem virsraksta – no kā sastāv saraksts
                    $types = collect($needs)->pluck('type');
                    // koncerti, kuriem sarakstā ir rindas (tikai tuvāko divu nedēļu laikā)
                    $concertTitles = collect($needs)->pluck('event')->filter()->unique('id')->pluck('title');
                    $hint = collect([
                        $concertTitles->isNotEmpty() ? 'Costume gaps for '.$concertTitles->implode(' and ') : null,
                        $types->contains('invite') ? $types->filter(fn ($t) => $t === 'invite')->count().' '.\Illuminate\Support\Str::plural('invite', $types->filter(fn ($t) => $t === 'invite')->count()).' not delivered' : null,
                        $types->contains('left') ? $types->filter(fn ($t) => $t === 'left')->count().' '.\Illuminate\Support\Str::plural('student', $types->filter(fn ($t) => $t === 'left')->count()).' left' : null,
                        $types->contains('season') ? 'Season report due' : null,
                    ])->filter()->implode(' · ');

                    // cik dienu līdz koncertam, cilvēkam saprotamā formā
                    $whenLabel = function ($event) {
                        $days = (int) now()->startOfDay()->diffInDays($event->starts_at->copy()->startOfDay());

                        return match (true) {
                            $days === 0 => 'Today',
                            $days === 1 => 'Tomorrow',
                            default => "in {$days} days",
                        };
                    };
                @endphp

                {{-- virsraksts: datums, sveiciens un galvenās darbības --}}
                <header data-reveal="" class="mb-9 flex flex-wrap items-end justify-between gap-5">
                    <div class="min-w-0">
                        <p class="mb-2 font-mono text-[11.5px] uppercase tracking-[0.1em] text-ink-soft">{{ now()->format('l, j F') }} · {{ $group->name }}</p>
                        <h1 class="font-display text-[length:clamp(32px,4vw,44px)] font-normal leading-[1.08] tracking-[-0.02em]">{{ $greeting }}</h1>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('admin.events.create') }}" class="rounded-full border border-line-strong px-5 py-2.5 text-[14.5px] font-medium hover:border-brand hover:bg-surface hover:text-brand">Add concert</a>
                        <a href="{{ route('admin.members.create') }}" class="rounded-full bg-brand px-5 py-2.5 text-[14.5px] font-medium text-paper hover:bg-ink">Add students</a>
                    </div>
                </header>

                {{-- Needs you --}}
                <section data-reveal="" data-reveal-delay="60" class="mb-6 rounded-[14px] border border-line bg-surface">
                    <div class="flex flex-wrap items-center gap-3 border-b border-line px-5 py-4 sm:px-6">
                        <h2 class="font-display text-[24px] font-normal leading-none tracking-[-0.01em]">Needs you</h2>
                        <span class="rounded-full px-2.5 py-0.5 font-mono text-[12px] font-medium {{ $needCount ? $lateChip : $goodChip }}">{{ $needCount }}</span>
                        @if($hint)
                            <span class="text-[14px] text-ink-soft">{{ $hint }}</span>
                        @endif
                    </div>

                    @if(empty($needs))
                        <div class="flex items-center gap-4 px-5 py-8 sm:px-6">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{ $goodChip }} text-lg" aria-hidden="true">✓</span>
                            <div>
                                <p class="text-[16px] font-medium">Nothing needs you today.</p>
                                <p class="text-[14.5px] text-ink-muted">
                                    {{ $overview['itemsOut'] }} {{ \Illuminate\Support\Str::plural('item', $overview['itemsOut']) }} out
                                    · no concert in the next {{ \App\Http\Controllers\Admin\DashboardController::NEEDS_WINDOW_DAYS }} days has costume gaps.
                                </p>
                            </div>
                        </div>
                    @else
                        {{-- redzamas ap trim rindām, pārējās ritinās rāmī --}}
                        <ul class="max-h-[15.5rem] overflow-y-auto overscroll-contain">
                            @foreach($needs as $row)
                                <li class="flex flex-wrap items-center gap-x-4 gap-y-2 border-b border-line-soft px-5 py-4 last:border-b-0 sm:px-6">
                                    <div class="min-w-0 flex-1">
                                        @switch($row['type'])
                                            @case('shortage')
                                                <p class="text-[15.5px]"><span class="font-semibold">{{ $row['costume']->name }}</span> <span class="text-ink-muted">isn't enough for {{ $row['event']->title }}</span></p>
                                                <p class="mt-0.5 font-mono text-[12px] text-ink-soft">{{ $row['total'] }} in stock · {{ $row['target'] }} needed · {{ $row['event']->starts_at->format('d.m') }}</p>
                                                @break
                                            @case('no_set')
                                                <p class="text-[15.5px]"><span class="font-semibold">{{ $row['students']->count() }} {{ \Illuminate\Support\Str::plural('student', $row['students']->count()) }}</span> <span class="text-ink-muted">{{ $row['students']->count() === 1 ? 'has' : 'have' }} no set, so their costumes for {{ $row['event']->title }} are unclear</span></p>
                                                <p class="mt-0.5 truncate font-mono text-[12px] text-ink-soft">{{ $row['students']->take(4)->pluck('name')->implode(', ') }}{{ $row['students']->count() > 4 ? ' …' : '' }}</p>
                                                @break
                                            @case('missing')
                                                <p class="text-[15.5px]"><span class="font-semibold">{{ $row['student']->name }}</span> <span class="text-ink-muted">is missing {{ $row['missing']->pluck('name')->implode(', ') }}</span></p>
                                                <p class="mt-0.5 font-mono text-[12px] text-ink-soft">for {{ $row['event']->title }} · {{ $row['event']->starts_at->format('d.m') }}</p>
                                                @break
                                            @case('invite')
                                                <p class="text-[15.5px]"><span class="font-semibold">{{ $row['student']->name }}</span> <span class="text-ink-muted">didn't get their invite email</span></p>
                                                <p class="mt-0.5 truncate font-mono text-[12px] text-ink-soft">{{ $row['student']->email }} · {{ $row['student']->invite_email_failed_at->format('d.m.Y') }}</p>
                                                @break
                                            @case('left')
                                                <p class="text-[15.5px]"><span class="font-semibold">{{ $row['notification']->data['student_name'] }}</span> <span class="text-ink-muted">left {{ $row['notification']->data['group_name'] }}</span></p>
                                                <p class="mt-0.5 font-mono text-[12px] text-ink-soft">{{ $row['notification']->created_at->diffForHumans() }}</p>
                                                @break
                                            @case('season')
                                                <p class="text-[15.5px]"><span class="font-semibold">The {{ $row['label'] }} season</span> <span class="text-ink-muted">is wrapping up</span></p>
                                                <p class="mt-0.5 font-mono text-[12px] text-ink-soft">export the report before summer break</p>
                                                @break
                                        @endswitch
                                    </div>

                                    {{-- uzlīme un viena darbība (vai divas, ja rindu var arī aizvērt) --}}
                                    <div class="flex shrink-0 flex-wrap items-center gap-2">
                                        @switch($row['type'])
                                            @case('shortage')
                                                <span class="rounded-full px-2.5 py-0.5 font-mono text-[12px] {{ $softChip }}">{{ $whenLabel($row['event']) }}</span>
                                                <span class="rounded-full px-2.5 py-0.5 font-mono text-[12px] {{ $lateChip }}">{{ $row['shortfall'] }} short</span>
                                                <a href="{{ route('admin.costumes.show', $row['costume']) }}" class="{{ $pill }}">Add items</a>
                                                @break
                                            @case('no_set')
                                                <span class="rounded-full px-2.5 py-0.5 font-mono text-[12px] {{ $softChip }}">{{ $whenLabel($row['event']) }}</span>
                                                <a href="{{ route('admin.members.index') }}" class="{{ $pill }}">Choose set</a>
                                                @break
                                            @case('missing')
                                                <span class="rounded-full px-2.5 py-0.5 font-mono text-[12px] {{ $softChip }}">{{ $whenLabel($row['event']) }}</span>
                                                <a href="{{ route('admin.members.show', $row['student']) }}#hand-out" class="{{ $pill }}">Hand out</a>
                                                @break
                                            @case('invite')
                                                <span class="rounded-full px-2.5 py-0.5 font-mono text-[12px] {{ $lateChip }}">Invite</span>
                                                @if($row['student']->must_change_password)
                                                    <form method="POST" action="{{ route('admin.members.resend-invite', $row['student']) }}">
                                                        @csrf
                                                        <button type="submit" class="{{ $pill }}">Resend invite</button>
                                                    </form>
                                                @endif
                                                <form method="POST" action="{{ route('admin.members.dismiss-invite', $row['student']) }}">
                                                    @csrf
                                                    <button type="submit" class="rounded-full px-3 py-1.5 text-[13px] font-medium text-ink-soft hover:text-ink">Dismiss</button>
                                                </form>
                                                @break
                                            @case('left')
                                                <span class="rounded-full px-2.5 py-0.5 font-mono text-[12px] {{ $softChip }}">Left</span>
                                                <form method="POST" action="{{ route('admin.notifications.dismiss', $row['notification']->id) }}">
                                                    @csrf
                                                    <button type="submit" class="{{ $pill }}">Dismiss</button>
                                                </form>
                                                @break
                                            @case('season')
                                                <a href="{{ route('admin.season-report.show') }}" target="_blank" class="{{ $pill }}">Export report</a>
                                                @break
                                        @endswitch
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                        @if($needCount > 3)
                            <p class="border-t border-line px-5 py-2.5 font-mono text-[12px] text-ink-soft sm:px-6">{{ $needCount - 3 }} more · scroll the list</p>
                        @endif
                    @endif
                </section>

                <div class="mb-6 grid gap-6 lg:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)]">
                    {{-- tuvākais koncerts un gatavība --}}
                    <section data-reveal="" data-reveal-delay="120" class="min-w-0 rounded-[14px] border border-line bg-surface p-5 sm:p-6"
                        x-data="{ open: false }">
                        <div class="mb-4 flex items-center justify-between gap-3">
                            <p class="font-mono text-[11.5px] uppercase tracking-[0.1em] text-ink-soft">Next on the calendar</p>
                            @if($nextEvent)
                                <span class="rounded-full px-2.5 py-0.5 font-mono text-[12px] {{ $goodChip }}">{{ $whenLabel($nextEvent) }}</span>
                            @endif
                        </div>

                        @if(! $nextEvent)
                            <p class="font-display text-[24px] leading-tight">No concert planned.</p>
                            <p class="mt-1 text-[14.5px] text-ink-muted">Add one to see how ready the group is for it.</p>
                            <div class="mt-5 flex flex-wrap gap-2">
                                <a href="{{ route('admin.events.create') }}" class="{{ $pill }}">Add concert</a>
                                @if($past->isNotEmpty())
                                    <button type="button" x-on:click="$dispatch('open-events', { tab: 'past' })" class="{{ $pill }}">Past concerts</button>
                                @endif
                            </div>
                        @else
                            <button type="button" x-on:click="$dispatch('open-events', { id: {{ $nextEvent->id }} })" class="block text-left hover:text-brand">
                                <span class="block font-display text-[28px] leading-tight tracking-[-0.01em]">{{ $nextEvent->title }}</span>
                            </button>
                            <p class="mt-1 font-mono text-[12.5px] text-ink-soft">
                                {{ $nextEvent->starts_at->format('D, d.m.Y · H:i') }}@if($nextEvent->location) · {{ $nextEvent->location }}@endif
                            </p>

                            @if(! $readiness['hasCostumes'])
                                <p class="mt-5 rounded-lg border border-dashed border-line-strong px-4 py-4 text-[14.5px] text-ink-muted">
                                    No costumes are attached to this concert yet, so there is no readiness to show.
                                    <a href="{{ route('admin.events.edit', $nextEvent) }}" class="font-medium text-brand hover:underline">Add costumes</a>
                                </p>
                            @else
                                @php $pct = $readiness['percent']; @endphp
                                <div class="mt-6 flex flex-wrap items-center gap-6">
                                    <div class="relative shrink-0">
                                        <svg viewBox="0 0 36 36" class="h-28 w-28 -rotate-90" aria-hidden="true">
                                            <circle cx="18" cy="18" r="15.915" fill="none" stroke-width="2.6" class="stroke-line" />
                                            <circle cx="18" cy="18" r="15.915" fill="none" stroke-width="2.6" stroke-linecap="round"
                                                class="stroke-brand" stroke-dasharray="{{ $pct }} {{ 100 - $pct }}" />
                                        </svg>
                                        <span class="absolute inset-0 flex items-center justify-center font-display text-[26px]">{{ $pct }}%</span>
                                    </div>
                                    <div class="min-w-0 text-[15px]">
                                        <p class="font-medium">{{ $readiness['ready'] }} of {{ $readiness['total'] }} students ready</p>
                                        <p class="text-ink-muted">
                                            @php $notReady = $readiness['total'] - $readiness['ready']; @endphp
                                            {{ $notReady === 0 ? 'Everyone has their full set' : $notReady.' still '.($notReady === 1 ? 'needs' : 'need').' a full set' }}
                                        </p>
                                        @if($readiness['absentCount'] > 0)
                                            <p class="font-mono text-[12px] text-ink-soft">{{ $readiness['absentCount'] }} not performing</p>
                                        @endif
                                        @if($notReady > 0)
                                            <button type="button" x-on:click="open = !open" class="mt-2 text-[14px] font-medium text-brand hover:underline">
                                                <span x-text="open ? 'Hide list' : 'See who →'">See who →</span>
                                            </button>
                                        @endif
                                    </div>
                                </div>

                                {{-- kam vēl trūkst kāds sava komplekta tērps --}}
                                <div x-ref="who" x-show="open" x-transition style="display: none" class="mt-5 border-t border-line pt-3">
                                    @foreach($readiness['students']->where('ready', false) as $r)
                                        <a href="{{ route('admin.members.show', $r['student']) }}" class="flex items-baseline justify-between gap-3 rounded-lg px-2 py-2 text-[14.5px] hover:bg-surface-sunk">
                                            <span class="min-w-0 truncate font-medium">{{ $r['student']->name }}</span>
                                            <span class="min-w-0 truncate text-right font-mono text-[12px] text-ink-soft">
                                                {{ $r['no_set'] ? 'no set chosen' : 'needs '.$r['missing']->pluck('name')->implode(', ') }}
                                            </span>
                                        </a>
                                    @endforeach
                                </div>

                                <p class="mt-5 text-[13px] text-ink-soft">
                                    Ready means holding one item of every costume the concert needs from the student's own set and the shared costumes.
                                </p>
                            @endif

                            <div class="mt-5 flex flex-wrap gap-2 border-t border-line pt-4">
                                <button type="button" x-on:click="$dispatch('open-events', { id: {{ $nextEvent->id }} })" class="{{ $pill }}">Details</button>
                                <a href="{{ route('admin.events.edit', $nextEvent) }}" class="{{ $pill }}">Edit concert</a>
                                <button type="button" x-on:click="$dispatch('open-events', { tab: 'upcoming' })" class="{{ $pill }}">All concerts</button>
                            </div>
                        @endif
                    </section>

                    <div class="flex min-w-0 flex-col gap-6">
                        {{-- nākamie koncerti pēc tuvākā --}}
                        @if($upcoming->count() > 1)
                            <section data-reveal="" data-reveal-delay="160" class="rounded-[14px] border border-line bg-surface p-5 sm:p-6">
                                <p class="mb-3 font-mono text-[11.5px] uppercase tracking-[0.1em] text-ink-soft">Later</p>
                                @foreach($upcoming->slice(1, 3) as $ev)
                                    <button type="button" x-data x-on:click="$dispatch('open-events', { id: {{ $ev->id }} })"
                                        class="flex w-full items-baseline justify-between gap-3 border-t border-line-soft py-2.5 text-left first-of-type:border-t-0 hover:text-brand">
                                        <span class="min-w-0 truncate text-[15px]">{{ $ev->title }}</span>
                                        <span class="shrink-0 font-mono text-[12px] text-ink-soft">{{ $ev->starts_at->format('d.m') }}</span>
                                    </button>
                                @endforeach
                                @if($upcoming->count() > 4 || $past->isNotEmpty())
                                    <button type="button" x-data x-on:click="$dispatch('open-events', { tab: 'upcoming' })" class="mt-2 text-[13.5px] font-medium text-brand hover:underline">Show all{{ $past->isNotEmpty() ? ' and past' : '' }}</button>
                                @endif
                            </section>
                        @endif

                        {{-- tērpi, kuriem vairs nav nevienas brīvas vienības --}}
                        <section data-reveal="" data-reveal-delay="200" class="rounded-[14px] border border-line bg-surface p-5 sm:p-6">
                            <p class="mb-3 font-mono text-[11.5px] uppercase tracking-[0.1em] text-ink-soft">Nothing spare</p>
                            @forelse($fullyOut as $row)
                                <a href="{{ route('admin.costumes.show', $row['id']) }}" class="flex items-baseline justify-between gap-3 border-t border-line-soft py-2.5 first-of-type:border-t-0 hover:text-brand">
                                    <span class="min-w-0 truncate text-[15px]">{{ $row['name'] }}</span>
                                    <span class="shrink-0 font-mono text-[12.5px] text-rust">{{ $row['count'] }} / {{ $row['count'] }} out</span>
                                </a>
                            @empty
                                <p class="text-[14.5px] text-ink-muted">Every costume still has an item to hand out.</p>
                            @endforelse
                            @if($fullyOut->isNotEmpty())
                                <p class="mt-3 text-[13px] text-ink-soft">These costumes have no item left to hand out. Everything else has spares.</p>
                            @endif
                        </section>
                    </div>
                </div>

                {{-- kluss kopsavilkums --}}
                <footer data-reveal="" data-reveal-delay="240" class="flex flex-wrap items-baseline gap-x-8 gap-y-3 border-t border-line pt-5">
                    @foreach([
                        [$overview['itemsOut'], 'out'],
                        [$overview['available'], 'available'],
                        [$overview['equippedCount'].' / '.$overview['memberCount'], 'students equipped'],
                    ] as [$value, $label])
                        <span class="flex items-baseline gap-2">
                            <span class="font-display text-[22px] leading-none">{{ $value }}</span>
                            <span class="text-[13.5px] text-ink-soft">{{ $label }}</span>
                        </span>
                    @endforeach
                    <span class="font-mono text-[12px] text-ink-soft sm:ml-auto">
                        Today: {{ $today['assigned'] }} issued, {{ $today['returned'] }} returned ·
                        <a href="{{ route('admin.activity') }}" class="text-brand hover:underline">Full log</a>
                    </span>
                </footer>

                {{-- pilns koncertu saraksts, pagātnes koncerti, detaļas, rediģēšana un dzēšana – atveras ar open-events --}}
                <x-events.timeline :upcoming="$upcoming" :past="$past" :can-manage="true" :manage-url="route('admin.events.index')" :modal-only="true" />
            @endif
        </div>
    </div>
</x-app-layout>
