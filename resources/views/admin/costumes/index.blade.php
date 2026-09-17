<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Costumes" subtitle="Manage costume sets and open their inventory.">
            <x-slot:actions>
                <a href="{{ route('admin.costumes.create') }}"
                    class="inline-flex items-center rounded-lg border border-brand-primary/20 bg-brand-primary px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-brand-accent focus:outline-none focus:ring-2 focus:ring-brand-secondary/50">
                    + Add Costume
                </a>
            </x-slot:actions>
        </x-page-header>
    </x-slot>

    <div class="space-y-3">
        @forelse($costumes as $costume)
            <div class="flex items-center justify-between gap-4 rounded-xl border border-gray-200 bg-white px-4 py-3 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex min-w-0 items-center gap-3">
                    @if($costume->image)
                        <img src="{{ $costume->imageUrl() }}" alt="" class="h-10 w-10 shrink-0 rounded-lg border border-gray-200 object-cover dark:border-gray-700">
                    @else
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-dashed border-gray-300 text-gray-300 dark:border-gray-600 dark:text-gray-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5l4.5-4.5a2 2 0 012.8 0l3.2 3.2m0 0l2-2a2 2 0 012.8 0L21 16.5M4 6h16a1 1 0 011 1v10a1 1 0 01-1 1H4a1 1 0 01-1-1V7a1 1 0 011-1z" />
                            </svg>
                        </span>
                    @endif
                    <div class="min-w-0">
                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $costume->name }}</span>
                        <span class="ml-2 text-xs text-gray-500 dark:text-gray-400">
                            {{ $costume->items_count }} {{ Str::plural('item', $costume->items_count) }}
                            @if($costume->items_out_count > 0) · {{ $costume->items_out_count }} out @endif
                        </span>
                    </div>
                </div>
                <a href="{{ route('admin.costumes.show', $costume) }}"
                    class="inline-flex shrink-0 items-center rounded-lg border border-brand-primary/25 bg-brand-light/50 px-3 py-1.5 text-xs font-semibold text-brand-accent transition hover:bg-brand-light/75 dark:border-brand-secondary/35 dark:bg-darkbrand-light/45 dark:text-brand-light">
                    Manage
                </a>
            </div>
        @empty
            <p class="rounded-xl border border-dashed border-gray-300 px-4 py-8 text-center text-sm text-gray-500 dark:border-gray-600 dark:text-gray-400">
                No costumes added yet.
            </p>
        @endforelse
    </div>
</x-app-layout>
