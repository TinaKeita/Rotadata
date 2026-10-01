{{-- grupas aktivitāte (agrāk atsevišķa lapa): rādītāji, žurnāls, izcēlumi un 6 nedēļu grafiks --}}
<section id="activity" class="mt-10 scroll-mt-24">
    <h2 class="ui-heading mb-1">Activity</h2>
    <p class="mb-5 text-sm text-ink-muted">Costume movements, trends and highlights for this group.</p>

        @php
            $o = $stats['overview'];
            $r = $stats['readiness'];
            $week = $stats['activityWeek'];
            $weekMax = max(1, $stats['weeks']->flatMap(fn($w) => [$w['assigned'], $w['returned']])->max());
        @endphp

        {{-- galvenie rādītāji --}}
        <section class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <div class="ui-card">
                <p class="text-xs font-medium text-ink-soft">Items checked out</p>
                <p class="mt-1 text-2xl font-semibold text-ink">{{ $o['itemsOut'] }}<span class="text-base font-normal text-ink-soft"> / {{ $o['totalItems'] }}</span></p>
                <p class="ui-help mt-0.5">{{ $o['utilisation'] }}% of inventory in use</p>
            </div>
            <div class="ui-alert ui-alert-good p-4">
                <p class="text-xs font-medium text-brand">Available now</p>
                <p class="mt-1 text-2xl font-semibold text-brand">{{ $o['available'] }}</p>
                <p class="mt-0.5 text-xs text-brand">ready to hand out</p>
            </div>
            <div class="ui-card">
                <p class="text-xs font-medium text-ink-soft">Students equipped</p>
                <p class="mt-1 text-2xl font-semibold text-ink">{{ $o['equippedCount'] }}<span class="text-base font-normal text-ink-soft"> / {{ $o['memberCount'] }}</span></p>
                <p class="ui-help mt-0.5">have at least one item</p>
            </div>
            <div class="ui-card">
                <p class="text-xs font-medium text-ink-soft">This week</p>
                <p class="mt-1 text-2xl font-semibold text-ink">{{ $week['assigned'] }} <span class="text-sm font-normal text-ink-soft">out</span> · {{ $week['returned'] }} <span class="text-sm font-normal text-ink-soft">back</span></p>
                <p class="ui-help mt-0.5">last 7 days</p>
            </div>
        </section>

        <section class="mt-6 grid gap-6 lg:grid-cols-3">
            {{-- pēdējās darbības --}}
            <div class="ui-card lg:col-span-2">
                <h3 class="ui-eyebrow mb-4">Recent activity <span class="font-normal text-ink-soft">· last 45 days</span></h3>

                @if($stats['feed']->isEmpty())
                    <p class="py-8 text-center text-sm text-ink-soft">No costume movements yet.</p>
                @else
                    <ol class="max-h-[32rem] space-y-3 overflow-y-auto pr-1">
                        @foreach($stats['feed'] as $e)
                            <li class="flex items-start gap-3 text-sm">
                                <span @class([
                                    'mt-1 h-2 w-2 shrink-0 rounded-full',
                                    'bg-brand' => $e['type'] === 'assigned',
                                    'bg-sand' => $e['type'] === 'returned',
                                    'bg-rust' => $e['type'] === 'taken_back',
                                    'bg-sky-500' => $e['type'] === 'handed_over',
                                    'bg-ink-soft' => $e['type'] === 'freed' || $e['type'] === 'left_group',
                                ])></span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-ink-muted">
                                        @if($e['type'] === 'assigned')
                                            <span class="font-semibold text-ink">{{ $e['who'] }}</span> took
                                            <span class="font-semibold">{{ $e['code'] }}</span>
                                        @elseif($e['type'] === 'returned')
                                            <span class="font-semibold text-ink">{{ $e['who'] }}</span> returned
                                            <span class="font-semibold">{{ $e['code'] }}</span>
                                        @elseif($e['type'] === 'handed_over')
                                            <span class="font-semibold text-ink">{{ $e['who'] }}</span> handed
                                            <span class="font-semibold">{{ $e['code'] }}</span> to
                                            <span class="font-semibold text-ink">{{ $e['actor'] ?? 'another member' }}</span>
                                        @elseif($e['type'] === 'freed')
                                            <span class="font-semibold">{{ $e['code'] }}</span> became available
                                            <span class="text-ink-soft">({{ $e['who'] }} removed)</span>
                                        @elseif($e['type'] === 'left_group')
                                            <span class="font-semibold">{{ $e['code'] }}</span> became available
                                            <span class="text-ink-soft">({{ $e['who'] }} left the group)</span>
                                        @else
                                            {{ $e['actor'] ?? 'A teacher' }} took
                                            <span class="font-semibold">{{ $e['code'] }}</span> back from
                                            <span class="font-semibold text-ink">{{ $e['who'] }}</span>
                                        @endif
                                        <span class="text-ink-soft">· {{ $e['costume'] }}</span>
                                    </p>
                                    <p class="ui-help">{{ $e['at']->diffForHumans() }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </div>

            {{-- krājuma stāvoklis un izcēlumi --}}
            <div class="space-y-6">
                <div class="ui-card">
                    <h3 class="ui-eyebrow">Collection readiness</h3>
                    <div class="mt-3 flex items-center gap-4">
                        <div class="relative shrink-0">
                            <svg viewBox="0 0 36 36" class="h-24 w-24 -rotate-90">
                                <circle cx="18" cy="18" r="15.915" fill="none" stroke-width="3" class="stroke-line" />
                                <circle cx="18" cy="18" r="15.915" fill="none" stroke-width="3" stroke-linecap="round"
                                    class="stroke-brand"
                                    stroke-dasharray="{{ $r['percent'] }} {{ 100 - $r['percent'] }}" />
                            </svg>
                            <span class="absolute inset-0 flex items-center justify-center text-lg font-semibold text-ink">{{ $r['percent'] }}%</span>
                        </div>
                        <div class="text-sm">
                            <p class="font-medium text-ink">{{ $r['back'] }} of {{ $r['total'] }} back</p>
                            <p class="text-ink-soft">{{ $r['out'] }} still out</p>
                        </div>
                    </div>
                </div>

                <div class="ui-card">
                    <h3 class="ui-eyebrow mb-3">Highlights</h3>
                    <dl class="space-y-3 text-sm">
                        <div>
                            <dt class="ui-help">Most-travelled item</dt>
                            <dd class="font-medium text-ink">
                                @if($stats['mostTravelled'])
                                    {{ $stats['mostTravelled']['code'] }}
                                    <span class="text-ink-soft">· worn by {{ $stats['mostTravelled']['travellers'] }} students</span>
                                @else
                                    <span class="text-ink-soft">not enough history yet</span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="ui-help">Busiest costume (last 4 months)</dt>
                            <dd class="font-medium text-ink">
                                @if($stats['busiestCostume'])
                                    {{ $stats['busiestCostume']['name'] }}
                                    <span class="text-ink-soft">· {{ $stats['busiestCostume']['times'] }} check-outs</span>
                                @else
                                    <span class="text-ink-soft">no check-outs yet</span>
                                @endif
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>
        </section>

        <section class="mt-6 grid gap-6 md:grid-cols-3">
            <div class="ui-card">
                <h3 class="ui-eyebrow mb-3">Out the longest</h3>
                @forelse($stats['longestOut'] as $row)
                    <div class="flex items-center justify-between gap-3 py-1.5 text-sm {{ !$loop->last ? 'border-b border-line-soft' : '' }}">
                        <span class="min-w-0 truncate text-ink-muted"><span class="font-semibold">{{ $row['code'] }}</span> · {{ $row['who'] }}</span>
                        <span class="shrink-0 font-medium {{ $row['days'] >= 30 ? 'text-rust' : 'text-ink-soft' }}">{{ $row['days'] }}d</span>
                    </div>
                @empty
                    <p class="text-sm text-ink-soft">Nothing checked out.</p>
                @endforelse
            </div>

            <div class="ui-card">
                <h3 class="ui-eyebrow mb-3">Holding the most</h3>
                @forelse($stats['topHolders'] as $row)
                    <div class="flex items-center justify-between gap-3 py-1.5 text-sm {{ !$loop->last ? 'border-b border-line-soft' : '' }}">
                        <span class="min-w-0 truncate text-ink-muted">{{ $row['name'] }}</span>
                        <span class="shrink-0 font-medium text-ink-soft">{{ $row['held'] }} items</span>
                    </div>
                @empty
                    <p class="text-sm text-ink-soft">Nobody has items yet.</p>
                @endforelse
            </div>

            <div class="ui-card">
                <h3 class="ui-eyebrow mb-3">Fully checked out</h3>
                @forelse($stats['fullyOut'] as $row)
                    <div class="flex items-center justify-between gap-3 py-1.5 text-sm {{ !$loop->last ? 'border-b border-line-soft' : '' }}">
                        <span class="min-w-0 truncate text-ink-muted">{{ $row['name'] }}</span>
                        <span class="shrink-0 font-medium text-ink-soft">{{ $row['count'] }}/{{ $row['count'] }}</span>
                    </div>
                @empty
                    <p class="text-sm text-ink-soft">Every costume still has stock.</p>
                @endforelse
            </div>
        </section>

        <section class="mt-6 grid gap-6 lg:grid-cols-2">
            <div class="ui-card">
                <h3 class="ui-eyebrow mb-4">In demand</h3>
                @forelse($stats['inDemand'] as $row)
                    <div class="mb-3 last:mb-0">
                        <div class="mb-1 flex items-center justify-between text-xs">
                            <span class="font-medium text-ink-muted">{{ $row['name'] }}</span>
                            <span class="text-ink-soft">{{ $row['out'] }}/{{ $row['total'] }} out</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-surface-sunk">
                            <div class="h-full rounded-full bg-brand" style="width: {{ $row['percent'] }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-ink-soft">No costumes yet.</p>
                @endforelse
            </div>

            <div class="ui-card">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="ui-eyebrow">Activity — last 6 weeks</h3>
                    <div class="flex items-center gap-3 text-xs text-ink-soft">
                        <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-sm bg-brand"></span>out</span>
                        <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-sm bg-sand"></span>back</span>
                    </div>
                </div>
                <div class="flex h-32 items-end justify-between gap-2">
                    @foreach($stats['weeks'] as $w)
                        <div class="flex flex-1 flex-col items-center gap-1">
                            <div class="flex w-full items-end justify-center gap-0.5" style="height: 100px">
                                <div class="w-2.5 rounded-t bg-brand" style="height: {{ max(2, $w['assigned'] / $weekMax * 100) }}%" title="{{ $w['assigned'] }} checked out"></div>
                                <div class="w-2.5 rounded-t bg-sand" style="height: {{ max(2, $w['returned'] / $weekMax * 100) }}%" title="{{ $w['returned'] }} returned"></div>
                            </div>
                            <span class="text-[10px] text-ink-soft">{{ $w['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
</section>
