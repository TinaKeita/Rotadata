<x-guest-layout>
    {{-- obligātā paroles maiņa pēc pieslēgšanās ar pagaidu paroli --}}
    <div class="mb-6 text-center">
        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-brand-primary dark:text-brand-secondary">Rotadata</p>
        <h1 class="mt-2 text-2xl font-semibold text-brand-accent dark:text-brand-light">Set your password</h1>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
            The password from the email works only once. Choose your own password to continue.
        </p>
    </div>

    <form method="POST" action="{{ route('password.change.update') }}" class="space-y-5">
        @csrf
        @method('PUT')

        <div>
            <x-input-label for="password" :value="__('New password')" />
            <x-text-input id="password" class="mt-1.5" type="password" name="password" required autofocus autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password_confirmation" :value="__('Confirm new password')" />
            <x-text-input id="password_confirmation" class="mt-1.5" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="pt-1">
            <x-primary-button class="w-full justify-center">
                {{ __('Save and continue') }}
            </x-primary-button>
        </div>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-4 text-center">
        @csrf
        <button type="submit" class="text-sm font-medium text-gray-600 hover:text-brand-primary dark:text-gray-300 dark:hover:text-brand-secondary">
            {{ __('Log out') }}
        </button>
    </form>
</x-guest-layout>
