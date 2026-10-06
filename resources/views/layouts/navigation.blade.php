@php
    // navigācijas dati (skaitītājus un grupas sagatavo View composer AppServiceProvider)
    $u = auth()->user();
    $isAdmin = $u?->hasRole('admin');
    $currentGroupId = request()->route('group')?->id;

    // iniciāļi un vārds konta "ovālam"
    $initials = collect(explode(' ', trim((string) ($u?->name ?? ''))))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
    $firstName = \Illuminate\Support\Str::before(trim((string) ($u?->name ?? '')), ' ');

    // grupas, kurās skolotājs ir dalībnieks (citas grupas sastāvā) – konta izvēlnē
    $memberships = $isAdmin ? ($navMemberGroups ?? collect()) : collect();

    // galvenās saites: [nosaukums, adrese, aktīva?, skaitītājs]
    $links = [[
        'Dashboard',
        route('dashboard'),
        request()->routeIs('dashboard') || request()->routeIs('admin.dashboard'),
        null,
    ]];

    if ($isAdmin) {
        // pašreizējās grupas sadaļas – tikai tad, ja skolotājam ir grupa
        if ($navGroup) {
            $links[] = ['Members', route('admin.members.index'), request()->routeIs('admin.members.*'), $navMembersCount ?? null];
            $links[] = ['Costumes', route('admin.costumes.index'), request()->routeIs('admin.costumes.*'), $navCostumesCount ?? null];
            $links[] = ['Concerts', route('admin.events.index'), request()->routeIs('admin.events.*'), $navEventsCount ?? null];
        }

        // katra skolotāja grupa – klikšķis pārslēdz uz to un atver tās iestatījumus (ar aktivitāti)
        foreach (($navOwnedGroups ?? collect()) as $g) {
            $links[] = [
                $g->name,
                route('admin.groups.open', $g),
                request()->routeIs('admin.group.*') && (int) $navGroup?->id === (int) $g->id,
                null,
            ];
        }
    } else {
        // studentam – katras grupas inventārs kā sava saite
        foreach (($navGroups ?? []) as $g) {
            $links[] = [
                $g->name,
                route('members.costumes.index', $g->id),
                request()->routeIs('members.costumes.*') && (int) $currentGroupId === (int) $g->id,
                null,
            ];
        }
    }
@endphp

{{-- vienkārša kapsula kā landing lapā: logotips, saites, profils un iziešana --}}
<header class="pointer-events-none sticky top-0 z-40 flex justify-center px-3 py-3 sm:px-4 sm:py-3.5"
    x-data="{ menu: false, account: false }" @keydown.escape.window="menu = false; account = false">
    <nav aria-label="Main"
        class="pointer-events-auto relative flex max-w-[calc(100%-4.5rem)] items-center gap-1.5 rounded-full border border-line bg-paper/80 py-[7px] pl-4 pr-[7px] shadow-nav backdrop-blur-[14px] lg:max-w-[calc(100%-9rem)]">

        <a href="{{ route('dashboard') }}" class="flex shrink-0 items-center gap-2 text-[15px] font-semibold tracking-[-0.01em] hover:text-brand">
            <img src="{{ asset('favicon.svg') }}" alt="" width="22" height="22" class="block h-[22px] w-[22px]">
            Rotadata
        </a>
        <span class="mx-1.5 hidden h-[18px] w-px shrink-0 bg-line lg:block"></span>

        {{-- saites lielā ekrānā --}}
        <div class="hidden min-w-0 items-center gap-0.5 overflow-x-auto lg:flex">
            @foreach($links as [$label, $href, $active, $badge])
                <x-nav-link :href="$href" :active="$active" :badge="$badge">{{ $label }}</x-nav-link>
            @endforeach

        </div>

        <span class="ml-auto lg:hidden"></span>
        <button type="button" @click="menu = !menu; account = false" :aria-expanded="menu.toString()" aria-controls="app-menu"
            class="shrink-0 rounded-full px-3 py-1.5 text-sm font-medium text-ink-muted hover:bg-surface-sunk hover:text-ink lg:hidden">
            Menu
        </button>

        {{-- konta ovāls: iniciāļi + vārds; izvēlnē profils, dalības citās grupās un iziešana --}}
        <div class="relative shrink-0 lg:ml-1.5" @click.outside="account = false">
            <button type="button" @click="account = !account; menu = false" :aria-expanded="account.toString()" aria-label="Account menu"
                class="flex items-center gap-2 rounded-full bg-ink py-1 pl-1 pr-3.5 text-sm font-medium text-paper hover:bg-brand">
                <span class="flex h-7 w-7 items-center justify-center rounded-full bg-paper/15 text-[12px] font-semibold">{{ $initials ?: '?' }}</span>
                <span class="hidden max-w-[9rem] truncate sm:inline">{{ $firstName }}</span>
            </button>

            <div x-show="account" x-transition.opacity style="display: none"
                class="absolute right-0 top-full mt-3 w-64 rounded-2xl border border-line bg-paper p-2 shadow-nav">
                <div class="border-b border-line px-3 pb-3 pt-2">
                    <p class="truncate text-[15px] font-semibold">{{ $u?->name }}</p>
                    <p class="truncate font-mono text-[11.5px] uppercase tracking-[0.08em] text-ink-soft">
                        {{ $isAdmin ? 'Teacher' : 'Student' }}@if($navGroup?->name) · {{ $navGroup->name }}@endif
                    </p>
                </div>

                @if($memberships->isNotEmpty())
                    <p class="px-3 pb-1 pt-3 font-mono text-[11px] uppercase tracking-[0.1em] text-ink-soft">My memberships</p>
                    @foreach($memberships as $g)
                        <a href="{{ route('members.costumes.index', $g->id) }}"
                            class="block truncate rounded-xl px-3 py-2 text-sm font-medium text-ink-muted hover:bg-surface-sunk hover:text-ink">{{ $g->name }}</a>
                    @endforeach
                @endif

                <div class="mt-1 border-t border-line pt-1">
                    <a href="{{ route('profile.edit') }}"
                        @class(['block rounded-xl px-3 py-2 text-sm font-medium hover:bg-surface-sunk hover:text-ink', 'bg-surface-sunk text-ink' => request()->routeIs('profile.*'), 'text-ink-muted' => ! request()->routeIs('profile.*')])>
                        Profile
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="block w-full rounded-xl px-3 py-2 text-left text-sm font-medium text-ink-muted hover:bg-surface-sunk hover:text-ink">
                            Log out
                        </button>
                    </form>
                </div>
            </div>
        </div>


        {{-- mazā ekrānā saites atveras zem kapsulas --}}
        <div id="app-menu" x-show="menu" @click.outside="menu = false" x-transition.opacity style="display: none"
            class="absolute left-0 right-0 top-full mt-2 flex min-w-[15rem] flex-col gap-0.5 rounded-2xl border border-line bg-paper p-2 shadow-nav lg:hidden">
            @foreach($links as [$label, $href, $active, $badge])
                <x-nav-link :href="$href" :active="$active" :badge="$badge" class="!flex w-full justify-between !rounded-xl py-2.5" @click="menu = false">{{ $label }}</x-nav-link>
            @endforeach
        </div>
    </nav>
</header>
