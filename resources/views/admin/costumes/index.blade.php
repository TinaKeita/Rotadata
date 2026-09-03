<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="text-2xl font-semibold text-brand-accent dark:text-brand-light leading-tight">Costumes</h2>
            <p class="text-sm text-gray-600 dark:text-gray-300">Manage costume sets and open their inventory.</p>
        </div>
    </x-slot>

    <div class="mb-5">
        <a href="{{ route('admin.costumes.create') }}"
            class="inline-flex items-center rounded-lg border border-brand-primary/20 bg-brand-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-accent focus:outline-none focus:ring-2 focus:ring-brand-secondary/50">
            Add Costume
        </a>
    </div>

    <div class="space-y-3">
        @forelse($costumes as $costume)
            <div class="flex items-center justify-between gap-4 rounded-xl border border-gray-200 bg-white px-4 py-3 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $costume->name }}</span>
                <a href="{{ route('admin.costumes.show', $costume) }}"
                    class="inline-flex items-center rounded-lg border border-brand-primary/25 bg-brand-light/50 px-3 py-1.5 text-xs font-semibold text-brand-accent transition hover:bg-brand-light/75 dark:border-brand-secondary/35 dark:bg-darkbrand-light/45 dark:text-brand-light">
                    View
                </a>
            </div>
        @empty
            <p class="rounded-xl border border-dashed border-gray-300 px-4 py-8 text-center text-sm text-gray-500 dark:border-gray-600 dark:text-gray-400">
                No costumes added yet.
            </p>
        @endforelse
    </div>
</x-app-layout>
