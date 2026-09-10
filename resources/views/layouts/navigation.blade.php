@php
    // sānjoslas dati (skaitītājus sagatavo View composer AppServiceProvider)
    $u = auth()->user();
    $isAdmin = $u?->hasRole('admin');

    // iniciāļi avatāram
    $initials = collect(explode(' ', trim((string) ($u?->name ?? ''))))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');

    $currentGroupId = request()->route('group')?->id;
@endphp

<nav class="flex h-full max-h-[calc(100dvh-5.5rem)] flex-col gap-6 overflow-y-auto px-3 py-4 sm:px-4 sm:py-5 lg:max-h-none">

    {{-- zīmols (tikai uz lielā ekrāna – mazajā tas ir augšējā joslā) --}}
    <a href="{{ route('dashboard') }}" class="hidden items-center gap-2.5 px-2 lg:flex">
        <x-application-logo class="h-7 w-auto text-white" />
        <span class="font-logo text-lg font-semibold italic tracking-wide text-white/95">Rotadata</span>
    </a>

    {{-- lietotāja kartiņa --}}
    <div class="flex items-center gap-3 rounded-xl bg-black/15 p-3">
        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white/15 text-sm font-semibold text-white">
            {{ $initials ?: '?' }}
        </span>
        <div class="min-w-0">
            <p class="truncate text-sm font-semibold text-white">{{ $u?->name }}</p>
            <p class="truncate text-xs text-white/55">
                {{ $isAdmin ? 'Teacher' : 'Student' }}@if($navGroup?->name) · {{ $navGroup->name }}@endif
            </p>
        </div>
    </div>

    {{-- galvenā izvēlne --}}
    <div class="flex flex-col gap-1">
        <p class="px-3 pb-1 text-[11px] font-semibold uppercase tracking-wider text-white/35">Menu</p>

        <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard') || request()->routeIs('admin.dashboard')">
            <x-slot:icon><x-nav-icon name="dashboard" /></x-slot:icon>
            Dashboard
        </x-nav-link>

        @if($isAdmin)
            <x-nav-link :href="route('admin.members.index')" :active="request()->routeIs('admin.members.*')" :badge="$navMembersCount ?? null">
                <x-slot:icon><x-nav-icon name="members" /></x-slot:icon>
                Members
            </x-nav-link>

            <x-nav-link :href="route('admin.costumes.index')" :active="request()->routeIs('admin.costumes.*')" :badge="$navCostumesCount ?? null">
                <x-slot:icon><x-nav-icon name="costumes" /></x-slot:icon>
                Costumes
            </x-nav-link>
        @else
            @foreach(($navGroups ?? []) as $g)
                <x-nav-link :href="route('members.costumes.index', $g->id)"
                    :active="request()->routeIs('members.costumes.*') && (int) $currentGroupId === (int) $g->id">
                    <x-slot:icon><x-nav-icon name="inventory" /></x-slot:icon>
                    {{ $g->name }}
                </x-nav-link>
            @endforeach
        @endif
    </div>

    {{-- konts --}}
    <div class="mt-auto flex flex-col gap-1 border-t border-white/10 pt-4">
        <p class="px-3 pb-1 text-[11px] font-semibold uppercase tracking-wider text-white/35">Account</p>

        <x-nav-link :href="route('profile.edit')" :active="request()->routeIs('profile.*')">
            <x-slot:icon><x-nav-icon name="profile" /></x-slot:icon>
            Profile
        </x-nav-link>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                class="group flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-white/65 transition duration-150 hover:bg-white/10 hover:text-white">
                <span class="shrink-0 text-white/75 group-hover:text-white"><x-nav-icon name="logout" /></span>
                <span class="flex-1 text-left">Log Out</span>
            </button>
        </form>
    </div>

</nav>
