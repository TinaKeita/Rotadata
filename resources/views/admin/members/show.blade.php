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
                <dd class="text-gray-800 dark:text-gray-200">{{ $user->created_at?->format('d.m.Y') }}</dd>
            </div>
        </dl>
    </div>

    @php
        $currentlyHolds = $user->costumeAssignments->whereNull('returned_at');
        $pastItems = $user->costumeAssignments->whereNotNull('returned_at');
    @endphp

    <div class="mt-5 max-w-2xl rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Currently holds ({{ $currentlyHolds->count() }})</h3>
        @if($currentlyHolds->isEmpty())
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Nothing checked out.</p>
        @else
            <ul class="mt-2 space-y-2 text-sm">
                @foreach($currentlyHolds as $log)
                    <li class="flex items-center justify-between gap-4">
                        <span class="font-medium text-gray-800 dark:text-gray-100">
                            {{ $log->item?->code ?? '—' }}
                            <span class="text-gray-400">· {{ $log->item?->costume?->name ?? 'deleted costume' }}</span>
                        </span>
                        <span class="text-gray-500 dark:text-gray-400">since {{ $log->assigned_at->format('d.m.Y') }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    <div class="mt-5 max-w-2xl rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Past items ({{ $pastItems->count() }})</h3>
        @if($pastItems->isEmpty())
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">No returned items yet.</p>
        @else
            <ul class="mt-2 space-y-2 text-sm">
                @foreach($pastItems as $log)
                    <li class="flex flex-wrap items-center justify-between gap-x-4 gap-y-1">
                        <span class="font-medium text-gray-800 dark:text-gray-100">
                            {{ $log->item?->code ?? '—' }}
                            <span class="text-gray-400">· {{ $log->item?->costume?->name ?? 'deleted costume' }}</span>
                        </span>
                        <span class="text-gray-500 dark:text-gray-400">
                            {{ $log->assigned_at->format('d.m.Y') }} &rarr; {{ $log->returned_at->format('d.m.Y') }}
                            @if($log->return_note === 'admin')
                                <span class="text-gray-400">(taken back)</span>
                            @elseif($log->return_note === 'transfer')
                                <span class="text-gray-400">(handed over)</span>
                            @endif
                        </span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</x-app-layout>
