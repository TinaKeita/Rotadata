<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Concerts" subtitle="Schedule concerts and let students know what's needed.">
            <x-slot:actions>
                <a href="{{ route('admin.events.create') }}"
                    class="inline-flex items-center rounded-lg border border-brand-primary/20 bg-brand-primary px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-brand-accent focus:outline-none focus:ring-2 focus:ring-brand-secondary/50">
                    + Add Concert
                </a>
            </x-slot:actions>
        </x-page-header>
    </x-slot>

    <div class="space-y-8">
        <section>
            <h3 class="mb-3 text-sm font-semibold text-gray-800 dark:text-gray-100">Upcoming</h3>
            <div class="space-y-3">
                @forelse($upcoming as $event)
                    @include('admin.events._row', ['event' => $event])
                @empty
                    <p class="rounded-xl border border-dashed border-gray-300 px-4 py-8 text-center text-sm text-gray-500 dark:border-gray-600 dark:text-gray-400">
                        No upcoming concerts. Add one to get started.
                    </p>
                @endforelse
            </div>
        </section>

        @if($past->isNotEmpty())
            <section>
                <h3 class="mb-3 text-sm font-semibold text-gray-800 dark:text-gray-100">Past</h3>
                <div class="space-y-3">
                    @foreach($past as $event)
                        @include('admin.events._row', ['event' => $event])
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-app-layout>
