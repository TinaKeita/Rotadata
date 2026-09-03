<x-app-layout>
    {{-- studentu sākumlapa --}}
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="text-2xl font-semibold text-brand-accent dark:text-brand-light leading-tight">
                {{ $group->name }} — My Inventory
            </h2>
            <p class="text-sm text-gray-600 dark:text-gray-300">
                Review your assigned costumes and unassign items when needed.
            </p>
        </div>
    </x-slot>

    <div class="mb-4">
        <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-brand-secondary dark:text-brand-light hover:text-brand-accent dark:hover:text-white transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
            Back to Dashboard
        </a>
    </div>

    <section class="space-y-3">
        @forelse($items as $item)
            <div class="flex items-center justify-between gap-4 rounded-xl border border-brand-secondary/15 dark:border-brand-light/20 bg-white dark:bg-gray-900/50 px-5 py-3.5 shadow-sm">
                <div class="flex items-center gap-4 min-w-0">
                    <span class="shrink-0 rounded-md border border-brand-primary/25 dark:border-brand-light/25 bg-brand-light/50 dark:bg-darkbrand-light/40 px-2.5 py-0.5 text-xs font-semibold tracking-wide text-brand-accent dark:text-brand-light">
                        #NR.{{ $item->id }}
                    </span>
                    <span class="truncate text-base font-medium text-gray-900 dark:text-gray-100">
                        {{ $item->costume->name }}
                    </span>
                </div>

                <form method="POST" action="{{ route('members.costumes.unassign', $item) }}" class="shrink-0">
                    @csrf
                    <button class="inline-flex items-center justify-center rounded-lg border border-brand-secondary/35 bg-brand-secondary px-3 py-1.5 text-sm font-semibold text-white transition hover:bg-brand-accent focus:outline-none focus:ring-2 focus:ring-brand-primary/50 dark:bg-darkbrand-secondary dark:hover:bg-darkbrand-accent dark:border-brand-light/35">
                        Unassign
                    </button>
                </form>
            </div>
        @empty
            <p class="py-6 text-center text-sm text-gray-500 dark:text-gray-400">No costumes assigned yet.</p>
        @endforelse
    </section>
</x-app-layout>
