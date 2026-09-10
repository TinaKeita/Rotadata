<x-guest-layout>
    <div class="mb-6 text-center">
        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-brand-primary dark:text-brand-secondary">Rotadata</p>
        <h1 class="mt-2 text-2xl font-semibold text-brand-accent dark:text-brand-light">Create your account</h1>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">Set up your teacher account and your first group.</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="name" :value="__('Your name')" />
            <x-text-input id="name" class="mt-1.5" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="mt-1.5" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="group_name" :value="__('Group name')" />
            <x-text-input id="group_name" class="mt-1.5" type="text" name="group_name" :value="old('group_name')" required autocomplete="off" />
            <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">Your ensemble or class, e.g. “Drama Club”. You can rename it later.</p>
            <x-input-error :messages="$errors->get('group_name')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="mt-1.5" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password_confirmation" :value="__('Confirm password')" />
            <x-text-input id="password_confirmation" class="mt-1.5" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="pt-1">
            <x-primary-button class="w-full justify-center">
                {{ __('Create account') }}
            </x-primary-button>
        </div>
    </form>

    <p class="mt-6 border-t border-gray-200 pt-5 text-center text-sm text-gray-600 dark:border-gray-700 dark:text-gray-300">
        Already have an account?
        <a href="{{ route('login') }}" class="font-semibold text-brand-accent hover:text-brand-primary dark:text-brand-light dark:hover:text-brand-secondary">
            Sign in
        </a>
    </p>
</x-guest-layout>
