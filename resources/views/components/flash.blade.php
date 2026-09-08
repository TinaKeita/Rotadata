{{-- kopīgs paziņojumu bloks – rāda session('success' | 'error' | 'warning') --}}
@if (session()->hasAny(['success', 'error', 'warning']))
    <div class="mb-5 space-y-2">
        @if (session('success'))
            <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 6000)"
                class="flex items-start gap-3 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 dark:border-emerald-500/40 dark:bg-emerald-900/20 dark:text-emerald-200">
                <span class="flex-1">{{ session('success') }}</span>
                <button type="button" @click="show = false" aria-label="Aizvērt" class="shrink-0 text-emerald-500 hover:text-emerald-700 dark:hover:text-emerald-300">&times;</button>
            </div>
        @endif

        @if (session('warning'))
            <div x-data="{ show: true }" x-show="show" x-transition
                class="flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800 dark:border-amber-500/40 dark:bg-amber-900/20 dark:text-amber-200">
                <span class="flex-1">{{ session('warning') }}</span>
                <button type="button" @click="show = false" aria-label="Aizvērt" class="shrink-0 text-amber-500 hover:text-amber-700 dark:hover:text-amber-300">&times;</button>
            </div>
        @endif

        @if (session('error'))
            <div x-data="{ show: true }" x-show="show" x-transition
                class="flex items-start gap-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800 dark:border-red-500/40 dark:bg-red-900/20 dark:text-red-200">
                <span class="flex-1">{{ session('error') }}</span>
                <button type="button" @click="show = false" aria-label="Aizvērt" class="shrink-0 text-red-500 hover:text-red-700 dark:hover:text-red-300">&times;</button>
            </div>
        @endif
    </div>
@endif
