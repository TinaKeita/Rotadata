<x-auth-layout :back-href="route('login')" back-label="Log in">
    <div class="mb-8 text-center">
        <p class="mb-3 font-mono text-xs uppercase tracking-[0.1em] text-brand">Teacher account</p>
        <h1 class="mb-2 font-display text-[36px] font-normal leading-[1.05] tracking-[-0.02em]">Start with one group.</h1>
        <p class="text-pretty text-[15.5px] text-ink-muted">Set up your teacher account and your first group.</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-5">
        @csrf

        <div>
            <label for="name" class="auth-label">Your name</label>
            <input id="name" class="auth-input" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name">
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div>
            <label for="email" class="auth-label">Email</label>
            <input id="email" class="auth-input" type="email" name="email" value="{{ old('email') }}" required autocomplete="username">
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <label for="group_name" class="auth-label">Group name</label>
            <input id="group_name" class="auth-input" type="text" name="group_name" value="{{ old('group_name') }}" required autocomplete="off">
            <p class="mt-1.5 text-[13px] text-ink-soft">Your ensemble or class, e.g. “Drama Club”. You can rename it later.</p>
            <x-input-error :messages="$errors->get('group_name')" class="mt-2" />
        </div>

        <div>
            <label for="password" class="auth-label">Password</label>
            <input id="password" class="auth-input" type="password" name="password" required autocomplete="new-password">
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <label for="password_confirmation" class="auth-label">Confirm password</label>
            <input id="password_confirmation" class="auth-input" type="password" name="password_confirmation" required autocomplete="new-password">
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="pt-1">
            <button type="submit" class="auth-button">Create account</button>
        </div>
    </form>

    <p class="mt-8 border-t border-line pt-6 text-center text-sm text-ink-muted">
        Already have an account?
        <a href="{{ route('login') }}" class="auth-link">Log in</a>
    </p>
</x-auth-layout>
