<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Member Details" subtitle="Overview of account information." />
    </x-slot>

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
