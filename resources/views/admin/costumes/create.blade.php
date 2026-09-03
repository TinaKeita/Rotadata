<x-app-layout>
    {{-- pievieno jaunu tērpu --}}
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="text-2xl font-semibold text-brand-accent dark:text-brand-light leading-tight">Create Costume</h2>
            <p class="text-sm text-gray-600 dark:text-gray-300">Add a new costume and generate inventory items.</p>
        </div>
    </x-slot>

    <div class="max-w-2xl rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <form method="POST" action="{{ route('admin.costumes.store') }}" class="space-y-5">
            @csrf

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Name</label>
                <input type="text" name="name" id="name" class="mt-1.5 w-full rounded-lg border-gray-300 px-3 py-2.5 text-gray-800 shadow-sm focus:border-brand-primary focus:ring-brand-secondary/50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200" required autocomplete="name">
            </div>

            <div>
                <label for="quantity" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Quantity</label>
                <input type="number" name="quantity" id="quantity" class="mt-1.5 w-full rounded-lg border-gray-300 px-3 py-2.5 text-gray-800 shadow-sm focus:border-brand-primary focus:ring-brand-secondary/50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200" required min="1" autocomplete="off">
            </div>

            <button class="inline-flex items-center rounded-lg border border-brand-primary/20 bg-brand-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-accent focus:outline-none focus:ring-2 focus:ring-brand-secondary/50">
                Create
            </button>
        </form>
    </div>
</x-app-layout>
