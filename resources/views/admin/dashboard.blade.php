<x-app-layout>
    {{-- admin sākumlapa --}}
    <x-slot name="header">
        @php $mainGroup = auth()->user()->adminGroups()->first(); @endphp
        <div class="flex flex-col gap-1">
            <h2 class="text-2xl font-semibold text-brand-accent dark:text-brand-light leading-tight">
                {{ $mainGroup?->name ?? 'Admin' }} Dashboard
            </h2>
            <p class="text-sm text-gray-600 dark:text-gray-300">
                Manage costumes and members from one place.
            </p>
        </div>
    </x-slot>

    @if(session('success'))
        <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:border-emerald-700/40 dark:bg-emerald-900/20 dark:text-emerald-300">
            {{ session('success') }}
        </div>
    @endif

    <section class="grid grid-cols-1 gap-6 lg:grid-cols-2">

        <div class="flex flex-col gap-4">
            <a href="{{ route('admin.costumes.index') }}"
                class="rounded-xl border border-brand-primary/20 bg-brand-primary px-5 py-4 text-center text-sm font-semibold text-white shadow-sm transition hover:bg-brand-accent focus:outline-none focus:ring-2 focus:ring-brand-secondary/50">
                Manage Costumes
            </a>

            <a href="{{ route('admin.costumes.create') }}"
                class="rounded-xl border border-brand-primary/20 bg-brand-light/55 px-5 py-4 text-center text-sm font-semibold text-brand-accent shadow-sm transition hover:bg-brand-light/75 dark:border-brand-secondary/35 dark:bg-darkbrand-light/45 dark:text-brand-light dark:hover:bg-darkbrand-light/60">
                Add New Costume
            </a>
        </div>

        <div class="min-h-[190px] rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <h3 class="mb-4 text-lg font-semibold text-gray-800 dark:text-gray-200">
                Statistics
            </h3>

            <div class="rounded-lg border border-dashed border-gray-300 px-4 py-10 text-center text-sm text-gray-500 dark:border-gray-600 dark:text-gray-400">
                To be continued...
            </div>
        </div>

    </section>
</x-app-layout>
