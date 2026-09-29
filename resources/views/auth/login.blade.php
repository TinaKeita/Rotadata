<x-auth-layout>
    <div class="mb-8 text-center">
        <p class="mb-3 font-mono text-xs uppercase tracking-[0.1em] text-brand">Welcome back</p>
        <h1 class="mb-2 font-display text-[36px] font-normal leading-[1.05] tracking-[-0.02em]">Log in</h1>
        <p class="text-pretty text-[15.5px] text-ink-muted">Sign in to manage groups and costume inventory.</p>
    </div>

    <x-auth-session-status class="mb-5 rounded-lg border border-line bg-brand-tint px-3.5 py-2.5 text-brand" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <div>
            <label for="email" class="auth-label">Email</label>
            <input id="email" class="auth-input" type="email" name="email" value="{{ old('email', request('email')) }}" required autofocus autocomplete="username">
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <label for="password" class="auth-label">Password</label>
            <x-password-input id="password" class="auth-input" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" name="remember" class="rounded border-line-strong bg-paper text-brand focus:ring-brand/30">
                <span class="ms-2 text-sm text-ink-muted">Remember me</span>
            </label>

            @if (Route::has('password.request'))
                <a class="auth-link text-sm" href="{{ route('password.request') }}">Forgot your password?</a>
            @endif
        </div>

        <div class="pt-1">
            <button type="submit" class="auth-button">Log in</button>
        </div>

        {{-- studentiem šeit paroli neatiestatīt – tas jālūdz skolotājam --}}
        <p class="text-center font-mono text-[11.5px] text-ink-soft">Students: ask your teacher to reset your password.</p>
    </form>

    @if (session('trashed_login_email'))
        {{-- parole sakrita ar dzēstu kontu – piedāvā to atjaunot --}}
        <div class="mt-6 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-500/40 dark:bg-amber-900/20 dark:text-amber-300">
            <p>Enter your password once more to restore this account and sign in.</p>
            <form method="POST" action="{{ route('login.restore') }}" class="mt-3 flex flex-wrap items-end gap-3">
                @csrf
                <input type="hidden" name="email" value="{{ session('trashed_login_email') }}">
                <div class="min-w-0 flex-1">
                    <label for="restore_password" class="sr-only">Password</label>
                    <x-password-input id="restore_password" class="auth-input" name="password" required autocomplete="current-password" />
                </div>
                <button type="submit" class="rounded-full bg-ink px-5 py-2.5 text-sm font-medium text-paper hover:bg-brand">
                    Restore account
                </button>
            </form>
        </div>
    @endif

    @if (Route::has('register'))
        {{-- jauns skolotājs izveido savu kontu un grupu --}}
        <p class="mt-8 border-t border-line pt-6 text-center text-sm text-ink-muted">
            New here?
            <a href="{{ route('register') }}" class="auth-link">Create a teacher account</a>
        </p>
    @endif

    <script>
        // ielogošanās e-pasta saite nes pagaidu paroli URL fragmentā (#autofill=...), nevis parametrā –
        // fragments serverim nekad netiek nosūtīts, tāpēc ieliekam formā tikai ar JS un tūlīt notīram no adreses joslas
        (function () {
            const match = location.hash.match(/^#autofill=(.+)$/);
            if (! match) {
                return;
            }

            const passwordField = document.getElementById('password');
            if (passwordField) {
                passwordField.value = decodeURIComponent(match[1]);
            }

            history.replaceState(null, '', location.pathname + location.search);
        })();
    </script>
</x-auth-layout>
