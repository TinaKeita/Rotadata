<x-guest-layout>
    {{-- lietotāja paroles ievade pēc QR skenēšanas --}}
    <div class="mb-6 text-center">
        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-brand-primary dark:text-brand-secondary">Rotadata</p>
        <h1 class="mt-2 text-2xl font-semibold text-brand-accent dark:text-brand-light">Confirm it's you</h1>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">Enter your password to claim this costume item.</p>
    </div>

    <div class="mb-5 rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-900/40 dark:text-gray-200">
        <span class="font-semibold text-brand-accent dark:text-brand-light">{{ $item->costume->name }}</span>
        @if($item->costume->group)
            <span class="text-gray-500 dark:text-gray-400"> &middot; {{ $item->costume->group->name }}</span>
        @endif
    </div>

    <form method="POST" action="{{ route('scan.authenticate', $item->qr_code) }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="mt-1.5" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="mt-1.5" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex items-center gap-3 pt-1">
            <x-primary-button class="justify-center">
                {{ __('Continue') }}
            </x-primary-button>
            <a href="{{ route('dashboard') }}" class="text-sm font-medium text-gray-600 hover:text-brand-primary dark:text-gray-300 dark:hover:text-brand-secondary">
                {{ __('Cancel') }}
            </a>
        </div>
    </form>
</x-guest-layout>
