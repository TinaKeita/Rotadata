<nav class="flex h-full max-h-[calc(100vh-6rem)] lg:max-h-none flex-col px-3 py-4 sm:px-4 sm:py-6 overflow-y-auto">

    {{-- User info block --}}
    <div class="mb-5 border-b border-white/15 pb-4">
        <p class="text-sm font-semibold text-white truncate">{{ Auth::user()->name }}</p>
        <p class="text-xs text-white/40 truncate">{{ Auth::user()->email }}</p>
    </div>

    {{-- Main links --}}
    <div class="flex flex-col gap-1.5">
        <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
            {{ __('Dashboard') }}
        </x-nav-link>

        @role('admin')
            <x-nav-link :href="route('admin.members.index')" :active="request()->routeIs('admin.members.*')">
                {{ __('Members') }}
            </x-nav-link>

            <x-nav-link :href="route('admin.costumes.index')" :active="request()->routeIs('admin.costumes.*')">
                {{ __('Costumes') }}
            </x-nav-link>
        @endrole
    </div>

    {{-- Bottom --}}
    <div class="mt-auto flex flex-col gap-1.5 border-t border-white/15 pt-4">
        <x-nav-link :href="route('profile.edit')" :active="request()->routeIs('profile.*')">
            {{ __('Profile') }}
        </x-nav-link>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                class="flex w-full items-center gap-2 rounded-md border-l-2 border-transparent px-3 py-2.5 text-sm font-medium text-white/70 hover:bg-black/15 hover:text-white transition duration-150">
                {{ __('Log Out') }}
            </button>
        </form>
    </div>

</nav>
