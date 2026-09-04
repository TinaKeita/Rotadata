<x-guest-layout>
    {{-- apstiprina tērpa vienības piešķiršanu pašam pieslēgtajam lietotājam --}}
    <div class="mb-6 text-center">
        <h1 class="text-2xl font-semibold text-brand-accent dark:text-brand-light">Assign this costume?</h1>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
            Signed in as <span class="font-semibold text-brand-accent dark:text-brand-light">{{ auth()->user()->name }}</span>
        </p>
    </div>

    <div class="mb-5 rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-900/40 dark:text-gray-200">
        <span class="font-semibold text-brand-accent dark:text-brand-light">{{ $item->costume->name }}</span>
        @if($item->costume->group)
            <span class="text-gray-500 dark:text-gray-400"> &middot; {{ $item->costume->group->name }}</span>
        @endif
    </div>

    <div class="flex items-center gap-3">
        <form method="POST" action="{{ route('scan.assign', $item->qr_code) }}">
            @csrf
            <x-primary-button class="justify-center">
                {{ __('Assign to me') }}
            </x-primary-button>
        </form>

        <a href="{{ route('dashboard') }}" class="text-sm font-medium text-gray-600 hover:text-brand-primary dark:text-gray-300 dark:hover:text-brand-secondary">
            {{ __('Cancel') }}
        </a>
    </div>
</x-guest-layout>
