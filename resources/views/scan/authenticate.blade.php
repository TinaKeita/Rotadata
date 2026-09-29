<x-guest-layout>
    {{-- lietotāja paroles ievade pēc QR skenēšanas --}}
    <div class="mb-6 text-center">
        <p class="font-mono text-[11.5px] tracking-[0.1em] uppercase text-brand">Rotadata</p>
        <h1 class="font-display text-[32px] font-normal leading-tight tracking-[-0.02em] text-ink mt-2">Confirm it's you</h1>
        <p class="mt-2 text-sm text-ink-muted">Enter your password to claim this costume item.</p>
    </div>

    <div class="mb-5 rounded-lg border border-line bg-surface-sunk px-4 py-3 text-sm text-ink-muted">
        <div>
            <span class="font-semibold text-brand">{{ $item->costume->name }}</span>
            @if($item->costume->group)
                <span class="text-ink-soft"> &middot; {{ $item->costume->group->name }}</span>
            @endif
        </div>
        <div class="mt-0.5 text-xs font-semibold tracking-wide text-ink-soft">{{ $item->code }}</div>
    </div>

    <form method="POST" action="{{ route('scan.authenticate', $item->qr_code) }}" class="space-y-5"
        x-data="{ lock: {{ (int) session('scanLockSeconds', 0) }} }"
        x-init="if (lock > 0) { const t = setInterval(() => { if (--lock <= 0) clearInterval(t) }, 1000) }">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="mt-1.5" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" x-bind:disabled="lock > 0" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-password-input id="password" class="ui-input mt-1.5" name="password" required autocomplete="current-password" x-bind:disabled="lock > 0" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        @if(session('scanLockSeconds'))
            <p x-show="lock > 0" class="text-sm font-medium text-rust">
                Too many attempts. Try again in <span x-text="lock"></span>s.
            </p>
        @endif

        <div class="flex items-center gap-3 pt-1">
            <x-primary-button class="justify-center" x-bind:disabled="lock > 0" x-bind:class="lock > 0 ? 'opacity-50 cursor-not-allowed' : ''">
                {{ __('Continue') }}
            </x-primary-button>
            <a href="{{ route('dashboard') }}" class="text-sm font-medium text-ink-muted hover:text-brand">
                {{ __('Cancel') }}
            </a>
        </div>
    </form>
</x-guest-layout>
