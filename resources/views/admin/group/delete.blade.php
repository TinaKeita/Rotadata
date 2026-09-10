<x-app-layout>
    {{-- grupas dzēšanas apstiprinājums: jāievada grupas nosaukums un parole --}}
    <x-slot name="header">
        <x-page-header title="Delete group" :subtitle="'Confirm you want to delete “'.$group->name.'”.'" />
    </x-slot>

    <div class="mb-4">
        <a href="{{ route('admin.group.settings') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-brand-secondary dark:text-brand-light hover:text-brand-accent dark:hover:text-white transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
            Back to Group settings
        </a>
    </div>

    <div class="max-w-2xl rounded-xl border border-red-200 bg-white p-6 shadow-sm dark:border-red-500/30 dark:bg-gray-800">

        {{-- kas tiks ietekmēts --}}
        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">What happens</h3>
        <ul class="mt-3 space-y-2 text-sm text-gray-600 dark:text-gray-300">
            <li>· <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $stats['costumes'] }}</span> costumes and <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $stats['items'] }}</span> items (with QR codes and history) are hidden now, erased on {{ now()->addDays(\App\Models\Group::PURGE_AFTER_DAYS)->format('d.m.Y') }}.</li>
            <li>· <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $stats['students_deactivated'] }}</span> students are only in this group — their accounts are deactivated and they're emailed.</li>
            <li>· <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $stats['students_kept'] }}</span> students are in other groups — their accounts keep working, they're just emailed.</li>
            <li>· You can restore everything from Group settings until the erase date.</li>
        </ul>

        @if($stats['items_out'] > 0)
            <p class="mt-4 rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-sm text-amber-800 dark:border-amber-500/40 dark:bg-amber-900/20 dark:text-amber-300">
                {{ $stats['items_out'] }} item(s) are still checked out to students. Take them back or have them
                returned before deleting the group.
            </p>
        @endif

        @if ($errors->any())
            <div class="mt-4 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-700 dark:bg-red-900/30 dark:text-red-300">
                <ul class="list-inside list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.group.destroy') }}" class="mt-6 space-y-5">
            @csrf
            @method('DELETE')

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                    Type the group name <span class="font-semibold">{{ $group->name }}</span> to confirm
                </label>
                <input type="text" name="name" id="name" required autocomplete="off" autofocus
                    class="mt-1.5 w-full rounded-lg border-gray-300 px-3 py-2.5 text-gray-800 shadow-sm focus:border-red-400 focus:ring-red-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Your password</label>
                <input type="password" name="password" id="password" required autocomplete="current-password"
                    class="mt-1.5 w-full rounded-lg border-gray-300 px-3 py-2.5 text-gray-800 shadow-sm focus:border-red-400 focus:ring-red-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" @disabled($stats['items_out'] > 0)
                    class="inline-flex items-center rounded-lg border border-red-300 bg-red-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-400 disabled:cursor-not-allowed disabled:opacity-50">
                    Delete group
                </button>
                <a href="{{ route('admin.group.settings') }}" class="text-sm font-medium text-gray-600 hover:text-brand-primary dark:text-gray-300 dark:hover:text-brand-secondary">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</x-app-layout>
