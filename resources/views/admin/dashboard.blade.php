<x-app-layout>
    {{-- admin sākumlapa – tērpu aprites pārskats --}}
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div class="flex flex-col gap-1">
                <h2 class="text-2xl font-semibold text-brand-accent dark:text-brand-light leading-tight">
                    {{ $group?->name ?? 'Admin' }} Dashboard
                </h2>
                <p class="text-sm text-gray-600 dark:text-gray-300">Costume circulation at a glance.</p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.costumes.create') }}"
                    class="inline-flex items-center rounded-lg border border-brand-primary/20 bg-brand-primary px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-brand-accent">
                    + Add costume
                </a>
                <a href="{{ route('admin.costumes.index') }}"
                    class="inline-flex items-center rounded-lg border border-brand-primary/25 bg-brand-light/50 px-3 py-1.5 text-xs font-semibold text-brand-accent transition hover:bg-brand-light/75 dark:border-brand-secondary/35 dark:bg-darkbrand-light/45 dark:text-brand-light">
                    Costumes
                </a>
                <a href="{{ route('admin.members.index') }}"
                    class="inline-flex items-center rounded-lg border border-brand-primary/25 bg-brand-light/50 px-3 py-1.5 text-xs font-semibold text-brand-accent transition hover:bg-brand-light/75 dark:border-brand-secondary/35 dark:bg-darkbrand-light/45 dark:text-brand-light">
                    Members
                </a>
            </div>
        </div>
    </x-slot>

    @if(is_null($stats))
        <div class="rounded-xl border border-dashed border-gray-300 px-4 py-12 text-center text-sm text-gray-500 dark:border-gray-600 dark:text-gray-400">
            You don't manage a group yet, so there's nothing to show here.
        </div>
    @else
        @php
            $o = $stats['overview'];
            $r = $stats['readiness'];
            $week = $stats['activityWeek'];
            $weekMax = max(1, $stats['weeks']->flatMap(fn($w) => [$w['assigned'], $w['returned']])->max());
        @endphp

        {{-- galvenie rādītāji --}}
        <section class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Items checked out</p>
                <p class="mt-1 text-2xl font-semibold text-gray-800 dark:text-gray-100">{{ $o['itemsOut'] }}<span class="text-base font-normal text-gray-400"> / {{ $o['totalItems'] }}</span></p>
                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ $o['utilisation'] }}% of inventory in use</p>
            </div>
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 shadow-sm dark:border-emerald-500/30 dark:bg-emerald-900/15">
                <p class="text-xs font-medium text-emerald-700 dark:text-emerald-300">Available now</p>
                <p class="mt-1 text-2xl font-semibold text-emerald-800 dark:text-emerald-200">{{ $o['available'] }}</p>
                <p class="mt-0.5 text-xs text-emerald-700/80 dark:text-emerald-300/80">ready to hand out</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Students equipped</p>
                <p class="mt-1 text-2xl font-semibold text-gray-800 dark:text-gray-100">{{ $o['equippedCount'] }}<span class="text-base font-normal text-gray-400"> / {{ $o['memberCount'] }}</span></p>
                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">have at least one item</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">This week</p>
                <p class="mt-1 text-2xl font-semibold text-gray-800 dark:text-gray-100">{{ $week['assigned'] }} <span class="text-sm font-normal text-gray-400">out</span> · {{ $week['returned'] }} <span class="text-sm font-normal text-gray-400">back</span></p>
                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">last 7 days</p>
            </div>
        </section>

        <section class="mt-6 grid gap-6 lg:grid-cols-3">
            {{-- pēdējās darbības --}}
            <div class="lg:col-span-2 rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h3 class="mb-4 text-sm font-semibold text-gray-800 dark:text-gray-100">Recent activity</h3>

                @if($stats['feed']->isEmpty())
                    <p class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">No costume movements yet.</p>
                @else
                    <ol class="max-h-80 space-y-3 overflow-y-auto pr-1">
                        @foreach($stats['feed'] as $e)
                            <li class="flex items-start gap-3 text-sm">
                                <span @class([
                                    'mt-1 h-2 w-2 shrink-0 rounded-full',
                                    'bg-brand-primary dark:bg-brand-secondary' => $e['type'] === 'assigned',
                                    'bg-emerald-500' => $e['type'] === 'returned',
                                    'bg-amber-500' => $e['type'] === 'taken_back',
                                    'bg-sky-500' => $e['type'] === 'handed_over',
                                    'bg-gray-400' => $e['type'] === 'freed',
                                ])></span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-gray-700 dark:text-gray-200">
                                        @if($e['type'] === 'assigned')
                                            <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $e['who'] }}</span> took
                                            <span class="font-semibold">{{ $e['code'] }}</span>
                                        @elseif($e['type'] === 'returned')
                                            <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $e['who'] }}</span> returned
                                            <span class="font-semibold">{{ $e['code'] }}</span>
                                        @elseif($e['type'] === 'handed_over')
                                            <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $e['who'] }}</span> handed
                                            <span class="font-semibold">{{ $e['code'] }}</span> to
                                            <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $e['actor'] ?? 'another member' }}</span>
                                        @elseif($e['type'] === 'freed')
                                            <span class="font-semibold">{{ $e['code'] }}</span> became available
                                            <span class="text-gray-400">({{ $e['who'] }} removed)</span>
                                        @else
                                            {{ $e['actor'] ?? 'A teacher' }} took
                                            <span class="font-semibold">{{ $e['code'] }}</span> back from
                                            <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $e['who'] }}</span>
                                        @endif
                                        <span class="text-gray-400">· {{ $e['costume'] }}</span>
                                    </p>
                                    <p class="text-xs text-gray-400 dark:text-gray-500">{{ $e['at']->diffForHumans() }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </div>

            {{-- gatavība un izcēlumi --}}
            <div class="space-y-6">
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Collection readiness</h3>
                    <div class="mt-3 flex items-center gap-4">
                        <div class="relative shrink-0">
                            <svg viewBox="0 0 36 36" class="h-24 w-24 -rotate-90">
                                <circle cx="18" cy="18" r="15.915" fill="none" stroke-width="3" class="stroke-gray-200 dark:stroke-gray-700" />
                                <circle cx="18" cy="18" r="15.915" fill="none" stroke-width="3" stroke-linecap="round"
                                    class="stroke-brand-primary dark:stroke-brand-secondary"
                                    stroke-dasharray="{{ $r['percent'] }} {{ 100 - $r['percent'] }}" />
                            </svg>
                            <span class="absolute inset-0 flex items-center justify-center text-lg font-semibold text-gray-800 dark:text-gray-100">{{ $r['percent'] }}%</span>
                        </div>
                        <div class="text-sm">
                            <p class="font-medium text-gray-800 dark:text-gray-100">{{ $r['back'] }} of {{ $r['total'] }} back</p>
                            <p class="text-gray-500 dark:text-gray-400">{{ $r['out'] }} still out</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <h3 class="mb-3 text-sm font-semibold text-gray-800 dark:text-gray-100">Highlights</h3>
                    <dl class="space-y-3 text-sm">
                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400">Most-travelled item</dt>
                            <dd class="font-medium text-gray-800 dark:text-gray-100">
                                @if($stats['mostTravelled'])
                                    {{ $stats['mostTravelled']['code'] }}
                                    <span class="text-gray-400">· worn by {{ $stats['mostTravelled']['travellers'] }} students</span>
                                @else
                                    <span class="text-gray-400">not enough history yet</span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400">Busiest costume (last 4 months)</dt>
                            <dd class="font-medium text-gray-800 dark:text-gray-100">
                                @if($stats['busiestCostume'])
                                    {{ $stats['busiestCostume']['name'] }}
                                    <span class="text-gray-400">· {{ $stats['busiestCostume']['times'] }} check-outs</span>
                                @else
                                    <span class="text-gray-400">no check-outs yet</span>
                                @endif
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>
        </section>

        {{-- jāpievērš uzmanība --}}
        <section class="mt-6 grid gap-6 md:grid-cols-3">
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h3 class="mb-3 text-sm font-semibold text-gray-800 dark:text-gray-100">Out the longest</h3>
                @forelse($stats['longestOut'] as $row)
                    <div class="flex items-center justify-between gap-3 py-1.5 text-sm {{ !$loop->last ? 'border-b border-gray-100 dark:border-gray-700/60' : '' }}">
                        <span class="min-w-0 truncate text-gray-700 dark:text-gray-200"><span class="font-semibold">{{ $row['code'] }}</span> · {{ $row['who'] }}</span>
                        <span class="shrink-0 font-medium {{ $row['days'] >= 30 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-500 dark:text-gray-400' }}">{{ $row['days'] }}d</span>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">Nothing checked out.</p>
                @endforelse
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h3 class="mb-3 text-sm font-semibold text-gray-800 dark:text-gray-100">Holding the most</h3>
                @forelse($stats['topHolders'] as $row)
                    <div class="flex items-center justify-between gap-3 py-1.5 text-sm {{ !$loop->last ? 'border-b border-gray-100 dark:border-gray-700/60' : '' }}">
                        <span class="min-w-0 truncate text-gray-700 dark:text-gray-200">{{ $row['name'] }}</span>
                        <span class="shrink-0 font-medium text-gray-500 dark:text-gray-400">{{ $row['held'] }} items</span>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">Nobody has items yet.</p>
                @endforelse
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h3 class="mb-3 text-sm font-semibold text-gray-800 dark:text-gray-100">Fully checked out</h3>
                @forelse($stats['fullyOut'] as $row)
                    <div class="flex items-center justify-between gap-3 py-1.5 text-sm {{ !$loop->last ? 'border-b border-gray-100 dark:border-gray-700/60' : '' }}">
                        <span class="min-w-0 truncate text-gray-700 dark:text-gray-200">{{ $row['name'] }}</span>
                        <span class="shrink-0 font-medium text-gray-500 dark:text-gray-400">{{ $row['count'] }}/{{ $row['count'] }}</span>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">Every costume still has stock.</p>
                @endforelse
            </div>
        </section>

        {{-- divi platie paneļi: pieprasījums un nedēļas aktivitāte --}}
        <section class="mt-6 grid gap-6 lg:grid-cols-2">
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h3 class="mb-4 text-sm font-semibold text-gray-800 dark:text-gray-100">In demand</h3>
                @forelse($stats['inDemand'] as $row)
                    <div class="mb-3 last:mb-0">
                        <div class="mb-1 flex items-center justify-between text-xs">
                            <span class="font-medium text-gray-700 dark:text-gray-200">{{ $row['name'] }}</span>
                            <span class="text-gray-500 dark:text-gray-400">{{ $row['out'] }}/{{ $row['total'] }} out</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700">
                            <div class="h-full rounded-full bg-brand-primary dark:bg-brand-secondary" style="width: {{ $row['percent'] }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">No costumes yet.</p>
                @endforelse
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Activity — last 6 weeks</h3>
                    <div class="flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                        <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-sm bg-brand-primary dark:bg-brand-secondary"></span>out</span>
                        <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-sm bg-emerald-500"></span>back</span>
                    </div>
                </div>
                <div class="flex h-32 items-end justify-between gap-2">
                    @foreach($stats['weeks'] as $w)
                        <div class="flex flex-1 flex-col items-center gap-1">
                            <div class="flex w-full items-end justify-center gap-0.5" style="height: 100px">
                                <div class="w-2.5 rounded-t bg-brand-primary dark:bg-brand-secondary" style="height: {{ max(2, $w['assigned'] / $weekMax * 100) }}%" title="{{ $w['assigned'] }} checked out"></div>
                                <div class="w-2.5 rounded-t bg-emerald-500" style="height: {{ max(2, $w['returned'] / $weekMax * 100) }}%" title="{{ $w['returned'] }} returned"></div>
                            </div>
                            <span class="text-[10px] text-gray-400 dark:text-gray-500">{{ $w['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</x-app-layout>
