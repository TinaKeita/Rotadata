<x-app-layout>
    <x-slot name="header">
        <x-page-header :eyebrow="auth()->user()->adminGroups()->value('name')" title="Costumes" subtitle="Every costume in your collection. Open one to manage its items and QR labels.">
            <x-slot:actions>
                <a href="{{ route('admin.costumes.create') }}"
                    class="ui-btn ui-btn-sm">
                    Add costume
                </a>
            </x-slot:actions>
        </x-page-header>
    </x-slot>

    <div class="space-y-3">
        @forelse($costumes as $costume)
            <div class="flex items-center justify-between gap-4 rounded-[14px] border border-line bg-surface px-4 py-3">
                <div class="flex min-w-0 items-center gap-3">
                    @if($costume->image)
                        <img src="{{ $costume->imageUrl() }}" alt="" class="h-10 w-10 shrink-0 rounded-lg border border-line object-cover">
                    @else
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-dashed border-line-strong text-ink-soft">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5l4.5-4.5a2 2 0 012.8 0l3.2 3.2m0 0l2-2a2 2 0 012.8 0L21 16.5M4 6h16a1 1 0 011 1v10a1 1 0 01-1 1H4a1 1 0 01-1-1V7a1 1 0 011-1z" />
                            </svg>
                        </span>
                    @endif
                    <div class="min-w-0">
                        <span class="text-sm font-medium text-ink">{{ $costume->name }}</span>
                        @if($costume->costumeSet)
                            <span class="ui-chip ui-chip-good ml-1.5">{{ $costume->costumeSet->name }}</span>
                        @endif
                        <span class="ml-2 text-xs text-ink-soft">
                            {{ $costume->items_count }} {{ Str::plural('item', $costume->items_count) }}
                            @if($costume->items_out_count > 0) · {{ $costume->items_out_count }} out @endif
                        </span>
                    </div>
                </div>
                <a href="{{ route('admin.costumes.show', $costume) }}"
                    class="ui-btn-ghost ui-btn-sm shrink-0">
                    Manage
                </a>
            </div>
        @empty
            <p class="rounded-[14px] border border-dashed border-line-strong px-4 py-8 text-center text-sm text-ink-soft">
                No costumes added yet.
            </p>
        @endforelse
    </div>
</x-app-layout>
