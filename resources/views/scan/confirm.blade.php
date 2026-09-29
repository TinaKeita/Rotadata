<x-guest-layout>
    {{-- apstiprina tērpa vienības piešķiršanu pašam pieslēgtajam lietotājam --}}
    <div class="mb-6 text-center">
        <h1 class="font-display text-[32px] font-normal leading-tight tracking-[-0.02em] text-ink">Assign this costume?</h1>
        <p class="mt-2 text-sm text-ink-muted">
            Signed in as <span class="font-semibold text-brand">{{ auth()->user()->name }}</span>
        </p>
    </div>

    <div class="mb-5 rounded-lg border border-line bg-surface-sunk px-4 py-3 text-sm text-ink-muted">
        <div class="flex items-center gap-3">
            @if($item->costume->image)
                <img src="{{ $item->costume->imageUrl() }}" alt="" class="h-14 w-14 shrink-0 rounded-lg border border-line object-cover">
            @endif
            <div class="min-w-0">
                <div>
                    <span class="font-semibold text-brand">{{ $item->costume->name }}</span>
                    @if($item->costume->group)
                        <span class="text-ink-soft"> &middot; {{ $item->costume->group->name }}</span>
                    @endif
                </div>
                <div class="mt-0.5 text-xs font-semibold tracking-wide text-ink-soft">{{ $item->code }}</div>
            </div>
        </div>
    </div>

    <div class="flex items-center gap-3">
        <form method="POST" action="{{ route('scan.assign', $item->qr_code) }}">
            @csrf
            <x-primary-button class="justify-center">
                {{ __('Assign to me') }}
            </x-primary-button>
        </form>

        <a href="{{ route('dashboard') }}" class="text-sm font-medium text-ink-muted hover:text-brand">
            {{ __('Cancel') }}
        </a>
    </div>
</x-guest-layout>
