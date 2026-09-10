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
                <div class="min-w-0">
                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $costume->name }}</span>
                    <span class="ml-2 text-xs text-gray-500 dark:text-gray-400">
                        {{ $costume->items_count }} {{ Str::plural('item', $costume->items_count) }}
                        @if($costume->items_out_count > 0) · {{ $costume->items_out_count }} out @endif
                    </span>
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
