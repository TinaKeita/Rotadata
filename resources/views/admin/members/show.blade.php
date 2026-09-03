<x-app-layout>
    {{-- konkrēta studenta skatīšanas lapa --}}
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="text-2xl font-semibold text-brand-accent dark:text-brand-light leading-tight">Member Details</h2>
            <p class="text-sm text-gray-600 dark:text-gray-300">Detailed view of this member account.</p>
        </div>
    </x-slot>

    <div class="mb-5">
        <a href="{{ route('admin.members.index') }}"
            class="inline-flex items-center rounded-lg border border-brand-primary/25 bg-brand-light/50 px-3 py-1.5 text-xs font-semibold text-brand-accent transition hover:bg-brand-light/75 dark:border-brand-secondary/35 dark:bg-darkbrand-light/45 dark:text-brand-light">
            Back to Members
        </a>
    </div>

    <div class="max-w-2xl rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <dl class="space-y-3 text-sm">
            <div class="flex items-center justify-between gap-4 border-b border-gray-200 pb-3 dark:border-gray-700">
                <dt class="font-medium text-gray-600 dark:text-gray-300">Name</dt>
                <dd class="font-semibold text-gray-900 dark:text-gray-100">{{ $user->name }}</dd>
            </div>

            <div class="flex items-center justify-between gap-4 border-b border-gray-200 pb-3 dark:border-gray-700">
                <dt class="font-medium text-gray-600 dark:text-gray-300">Email</dt>
                <dd class="text-gray-800 dark:text-gray-200">{{ $user->email }}</dd>
            </div>

            <div class="flex items-center justify-between gap-4">
                <dt class="font-medium text-gray-600 dark:text-gray-300">Created</dt>
                <dd class="text-gray-800 dark:text-gray-200">{{ $user->created_at }}</dd>
            </div>
        </dl>
    </div>
</x-app-layout>
