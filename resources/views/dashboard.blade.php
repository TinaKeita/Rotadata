<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="text-2xl font-semibold text-brand-accent dark:text-brand-light leading-tight">
                Member Dashboard
            </h2>
            <p class="text-sm text-gray-600 dark:text-gray-300">
                Browse your group inventories and manage assigned costumes.
            </p>
        </div>
    </x-slot>

    <section class="space-y-6">
        @php
            $groups = auth()->user()->memberGroups;
        @endphp

        <div class="rounded-2xl border border-brand-primary/20 dark:border-brand-light/15 bg-gradient-to-r from-brand-light/40 via-white to-brand-light/20 dark:from-darkbrand-light/40 dark:via-gray-800 dark:to-darkbrand-light/20 p-6 sm:p-8 shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h3 class="text-2xl font-semibold text-brand-accent dark:text-brand-light">
                        Your groups and inventories
                    </h3>
                    <p class="mt-2 max-w-2xl text-sm sm:text-base text-gray-700 dark:text-gray-200">
                        Open your inventory directly from your assigned group.
                    </p>
                </div>
                <p class="text-sm font-medium text-brand-secondary dark:text-brand-light">
                    <span class="font-semibold">{{ $groups->count() }}</span>
                    {{ Str::plural('group', $groups->count()) }} available
                </p>
            </div>
        </div>

        <div class="grid gap-5 lg:grid-cols-2">
            @foreach($groups as $group)
                <article class="group rounded-2xl border border-brand-secondary/15 dark:border-brand-light/20 bg-white dark:bg-gray-900/50 p-6 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-lg">
                    <div class="mb-5 flex items-start justify-between gap-4">
                        <div>
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                                {{ $group->name }}
                            </h3>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                                Teacher: {{ $group->admin?->name ?? 'Not assigned' }}
                            </p>
                        </div>
                        <span class="rounded-full border border-brand-primary/30 dark:border-brand-light/30 bg-brand-light/50 dark:bg-darkbrand-light/60 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-brand-accent dark:text-brand-light">
                            Group
                        </span>
                    </div>

                    <a href="{{ route('members.costumes.index', $group->id ?? 0) }}"
                        class="inline-flex items-center justify-center rounded-lg border border-brand-secondary/35 dark:border-brand-light/35 bg-brand-secondary px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-accent focus:outline-none focus:ring-2 focus:ring-brand-primary/50 dark:bg-darkbrand-secondary dark:hover:bg-darkbrand-accent">
                        My Inventory
                    </a>
                </article>
            @endforeach
        </div>
    </section>
</x-app-layout>
