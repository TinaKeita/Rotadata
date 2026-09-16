<x-guest-layout>
    <div class="mb-6 text-center">
        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-brand-primary dark:text-brand-secondary">Rotadata</p>
        <h1 class="mt-2 text-2xl font-semibold text-brand-accent dark:text-brand-light">Welcome Back</h1>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">Sign in to manage groups and costume inventory.</p>
    </div>

    <x-auth-session-status class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-emerald-700 dark:border-emerald-500/40 dark:bg-emerald-900/20 dark:text-emerald-300" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="mt-1.5" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="mt-1.5"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between gap-3">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-brand-primary shadow-sm focus:ring-brand-secondary/60 dark:border-gray-700 dark:bg-gray-900 dark:text-brand-secondary dark:focus:ring-brand-secondary/40" name="remember">
                <span class="ms-2 text-sm text-gray-600 dark:text-gray-300">{{ __('Remember me') }}</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-sm font-medium text-brand-accent hover:text-brand-primary focus:outline-none focus:ring-2 focus:ring-brand-secondary/50 dark:text-brand-light dark:hover:text-brand-secondary" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif
        </div>

        <div class="pt-1">
            <x-primary-button class="w-full justify-center">
                {{ __('Log in') }}
            </x-primary-button>
        </div>
    </form>

    @if (session('trashed_login_email'))
        {{-- parole sakrita ar dzēstu kontu – piedāvā to atjaunot --}}
        <div class="mt-5 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-500/40 dark:bg-amber-900/20 dark:text-amber-300">
            <p>Enter your password once more to restore this account and sign in.</p>
            <form method="POST" action="{{ route('login.restore') }}" class="mt-3 flex flex-wrap items-end gap-3">
                @csrf
                <input type="hidden" name="email" value="{{ session('trashed_login_email') }}">
                <div class="min-w-0 flex-1">
                    <x-input-label for="restore_password" value="Password" class="sr-only" />
                    <x-text-input id="restore_password" class="mt-1 w-full" type="password" name="password" required autocomplete="current-password" />
                </div>
                <button type="submit" class="inline-flex items-center rounded-lg border border-brand-primary/20 bg-brand-primary px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-accent focus:outline-none focus:ring-2 focus:ring-brand-secondary/50">
                    Restore account
                </button>
            </form>
        </div>
    @endif

    @if (Route::has('register'))
        {{-- jauns skolotājs izveido savu kontu un grupu --}}
        <p class="mt-6 border-t border-gray-200 pt-5 text-center text-sm text-gray-600 dark:border-gray-700 dark:text-gray-300">
            New here?
            <a href="{{ route('register') }}" class="font-semibold text-brand-accent hover:text-brand-primary dark:text-brand-light dark:hover:text-brand-secondary">
                Create a teacher account
            </a>
        </p>
    @endif
</x-guest-layout>
