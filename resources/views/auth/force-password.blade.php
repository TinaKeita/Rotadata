<x-guest-layout>
    {{-- obligātā paroles maiņa pēc pieslēgšanās ar pagaidu paroli --}}
    <div class="mb-6 text-center">
        <p class="font-mono text-[11.5px] tracking-[0.1em] uppercase text-brand">Rotadata</p>
        <h1 class="font-display text-[32px] font-normal leading-tight tracking-[-0.02em] text-ink mt-2">Set your password</h1>
        <p class="mt-2 text-sm text-ink-muted">
            The password from the email works only once. Choose your own password to continue.
            You can also fix how your name is written.
        </p>
    </div>

    <form method="POST" action="{{ route('password.change.update') }}" class="space-y-5">
        @csrf
        @method('PUT')

        {{-- vārds, ko ievadīja skolotājs; students var to izlabot, bet nav jāmaina --}}
        <div>
            <x-input-label for="name" :value="__('Your name')" />
            <x-text-input id="name" class="mt-1.5" type="text" name="name" :value="old('name', auth()->user()->name)" required maxlength="255" autocomplete="name" />
            <p class="ui-help mt-1.5">This is how your teacher wrote it. Change it only if it's wrong.</p>
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" :value="__('New password')" />
            <x-password-input id="password" class="ui-input mt-1.5" name="password" required autofocus autocomplete="new-password" />
            <p class="ui-help mt-1.5">At least 8 characters, with an uppercase and a lowercase letter, a number and a special character (e.g. ! ? - #).</p>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password_confirmation" :value="__('Confirm new password')" />
            <x-password-input id="password_confirmation" class="ui-input mt-1.5" name="password_confirmation" required autocomplete="new-password" />
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
        <button type="submit" class="text-sm font-medium text-ink-muted hover:text-brand">
            {{ __('Log out') }}
        </button>
    </form>
</x-guest-layout>
