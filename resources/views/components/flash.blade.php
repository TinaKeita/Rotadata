{{-- kopīgs paziņojumu bloks – rāda session('success' | 'error' | 'warning') --}}
@if (session()->hasAny(['success', 'error', 'warning']))
    <div class="mb-5 space-y-2">
        @if (session('success'))
            <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 6000)"
                class="ui-alert ui-alert-good flex items-start gap-3 font-medium">
                <span class="flex-1 whitespace-pre-line">{{ session('success') }}</span>
                <button type="button" @click="show = false" aria-label="Close" class="shrink-0 opacity-60 hover:opacity-100">&times;</button>
            </div>
        @endif

        @if (session('warning'))
            <div x-data="{ show: true }" x-show="show" x-transition
                class="ui-alert ui-alert-warn flex items-start gap-3 font-medium">
                <span class="flex-1 whitespace-pre-line">{{ session('warning') }}</span>
                <button type="button" @click="show = false" aria-label="Close" class="shrink-0 opacity-60 hover:opacity-100">&times;</button>
            </div>
        @endif

        @if (session('error'))
            <div x-data="{ show: true }" x-show="show" x-transition
                class="ui-alert ui-alert-error flex items-start gap-3 font-medium">
                <span class="flex-1 whitespace-pre-line">{{ session('error') }}</span>
                <button type="button" @click="show = false" aria-label="Close" class="shrink-0 opacity-60 hover:opacity-100">&times;</button>
            </div>
        @endif
    </div>
@endif
