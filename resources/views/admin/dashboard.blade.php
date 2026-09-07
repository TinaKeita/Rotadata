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

            @php
                $group = auth()->user()->adminGroups()->first();
                $itemIds = $group
                    ? \App\Models\CostumeItem::whereHas('costume', fn($q) => $q->where('group_id', $group->id))->pluck('id')
                    : collect();
                $totalItems = $itemIds->count();
                $itemsOut = \App\Models\CostumeItemAssignment::whereIn('costume_item_id', $itemIds)->whereNull('returned_at')->count();
                $itemsAvailable = max(0, $totalItems - $itemsOut);
                $returnedRecently = \App\Models\CostumeItemAssignment::whereIn('costume_item_id', $itemIds)
                    ->where('returned_at', '>=', now()->subDays(7))
                    ->count();
            @endphp

            <dl class="grid grid-cols-2 gap-3">
                <div class="rounded-lg border border-gray-200 px-4 py-3 dark:border-gray-700">
                    <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Items total</dt>
                    <dd class="mt-1 text-2xl font-semibold text-gray-800 dark:text-gray-100">{{ $totalItems }}</dd>
                </div>
                <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 dark:border-amber-500/30 dark:bg-amber-900/15">
                    <dt class="text-xs font-medium text-amber-700 dark:text-amber-300">Currently out</dt>
                    <dd class="mt-1 text-2xl font-semibold text-amber-800 dark:text-amber-200">{{ $itemsOut }}</dd>
                </div>
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 dark:border-emerald-500/30 dark:bg-emerald-900/15">
                    <dt class="text-xs font-medium text-emerald-700 dark:text-emerald-300">Available</dt>
                    <dd class="mt-1 text-2xl font-semibold text-emerald-800 dark:text-emerald-200">{{ $itemsAvailable }}</dd>
                </div>
                <div class="rounded-lg border border-gray-200 px-4 py-3 dark:border-gray-700">
                    <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Returned (7 days)</dt>
                    <dd class="mt-1 text-2xl font-semibold text-gray-800 dark:text-gray-100">{{ $returnedRecently }}</dd>
                </div>
            </dl>
        </div>

    </section>
</x-app-layout>
