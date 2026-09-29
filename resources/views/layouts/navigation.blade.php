@php
    // navigācijas dati (skaitītājus sagatavo View composer AppServiceProvider)
    $u = auth()->user();
    $isAdmin = $u?->hasRole('admin');

    // iniciāļi avatāram
    $initials = collect(explode(' ', trim((string) ($u?->name ?? ''))))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');

    $currentGroupId = request()->route('group')?->id;

    // galvenās saites: [nosaukums, adrese, aktīva?, skaitītājs]
    $links = [[
        'Dashboard',
        route('dashboard'),
        request()->routeIs('dashboard') || request()->routeIs('admin.dashboard'),
        null,
    ]];

    if ($isAdmin) {
        $links[] = ['Members', route('admin.members.index'), request()->routeIs('admin.members.*'), $navMembersCount ?? null];
        $links[] = ['Costumes', route('admin.costumes.index'), request()->routeIs('admin.costumes.*'), $navCostumesCount ?? null];
        $links[] = ['Concerts', route('admin.events.index'), request()->routeIs('admin.events.*'), $navEventsCount ?? null];
        $links[] = ['Activity', route('admin.activity'), request()->routeIs('admin.activity'), null];
        $links[] = ['Settings', route('admin.group.settings'), request()->routeIs('admin.group.*'), null];
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

    // skolotājs var būt arī cita skolotāja grupas dalībnieks – šīs grupas rāda konta izvēlnē
    $memberships = $isAdmin ? ($navMemberGroups ?? collect()) : collect();
@endphp

<header class="pointer-events-none sticky top-0 z-40 flex justify-center px-3 py-3 sm:px-4 sm:py-3.5"
    x-data="{ menu: false, account: false }" @keydown.escape.window="menu = false; account = false">
    <nav aria-label="Main"
        class="pointer-events-auto relative flex w-full max-w-[1100px] items-center gap-1.5 rounded-full border border-line bg-paper/80 py-[7px] pl-4 pr-[7px] shadow-nav backdrop-blur-[14px]">

        <a href="{{ route('dashboard') }}" class="flex shrink-0 items-center gap-2 text-[15px] font-semibold tracking-[-0.01em] hover:text-brand">
            <img src="{{ asset('favicon.svg') }}" alt="" width="22" height="22" class="block h-[22px] w-[22px]">
            Rotadata
        </a>
        <span class="mx-1.5 hidden h-[18px] w-px shrink-0 bg-line lg:block"></span>

        {{-- saites lielā ekrānā --}}
        <div class="hidden min-w-0 flex-1 items-center gap-0.5 overflow-x-auto lg:flex">
            @foreach($links as [$label, $href, $active, $badge])
                <x-nav-link :href="$href" :active="$active" :badge="$badge">{{ $label }}</x-nav-link>
            @endforeach
        </div>

        <div class="ml-auto flex shrink-0 items-center gap-2">
            {{-- tumšā režīma pārslēgs --}}
            <div class="toggle-switch shrink-0">
                <label class="switch-label">
                    <input type="checkbox" class="checkbox" x-model="darkMode" aria-label="Toggle dark mode">
                    <span class="slider"></span>
                </label>
            </div>

            <button type="button" @click="menu = !menu; account = false" :aria-expanded="menu.toString()" aria-controls="app-menu"
                class="shrink-0 rounded-full px-3 py-1.5 text-sm font-medium text-ink-muted hover:bg-surface-sunk hover:text-ink lg:hidden">
                Menu
            </button>

            {{-- konta izvēlne: vārds, loma, profils, dalības citās grupās, iziešana --}}
            <div class="relative" @click.outside="account = false">
                <button type="button" @click="account = !account; menu = false" :aria-expanded="account.toString()" aria-label="Account menu"
                    class="flex h-9 w-9 items-center justify-center rounded-full bg-ink text-[13px] font-semibold text-paper hover:bg-brand">
                    {{ $initials ?: '?' }}
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
                                @class([
                                    'block truncate rounded-xl px-3 py-2 text-sm font-medium hover:bg-surface-sunk hover:text-ink',
                                    'bg-surface-sunk text-ink' => request()->routeIs('members.costumes.*') && (int) $currentGroupId === (int) $g->id,
                                    'text-ink-muted' => ! (request()->routeIs('members.costumes.*') && (int) $currentGroupId === (int) $g->id),
                                ])>{{ $g->name }}</a>
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
        </div>

        {{-- mazā ekrānā saites atveras zem kapsulas --}}
        <div id="app-menu" x-show="menu" @click.outside="menu = false" x-transition.opacity style="display: none"
            class="absolute inset-x-0 top-full mt-2 flex flex-col gap-0.5 rounded-2xl border border-line bg-paper p-2 shadow-nav lg:hidden">
            @foreach($links as [$label, $href, $active, $badge])
                <x-nav-link :href="$href" :active="$active" :badge="$badge" class="!flex w-full justify-between !rounded-xl py-2.5" @click="menu = false">{{ $label }}</x-nav-link>
            @endforeach
        </div>
    </nav>
</header>
