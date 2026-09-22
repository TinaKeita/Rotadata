<x-guest-layout>
    <div class="mb-4">
        <a href="{{ route('login') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-brand-secondary dark:text-brand-light hover:text-brand-accent dark:hover:text-white transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
            Back to login
        </a>
    </div>

    <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
        {{ __('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.') }}
    </div>

    <div class="mb-4 rounded-lg border border-brand-primary/20 bg-brand-light/30 px-3 py-2 text-sm text-brand-accent dark:border-brand-light/20 dark:bg-darkbrand-light/20 dark:text-brand-light">
        This only works for teacher accounts. Students should ask their teacher to reset their password from the admin dashboard.
    </div>

    {{-- pēc mēģinājuma parāda skaidru, uzkrītošu paziņojumu, ja e-pasts pieder studenta kontam --}}
    @if (session('notice'))
        <div class="mb-4 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800 dark:border-amber-500/40 dark:bg-amber-900/20 dark:text-amber-300">
            {{ session('notice') }}
        </div>
    @endif

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                {{ __('Email Password Reset Link') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
