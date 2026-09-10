<x-app-layout>
    {{-- grupas iestatījumi: pārsaukšana, dzēšana un nesen dzēstas grupas atjaunošana --}}
    <x-slot name="header">
        <x-page-header title="Group settings" subtitle="Rename your group, or delete it with a 30-day recovery window." />
    </x-slot>

    <div class="mb-4">
        <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-brand-secondary dark:text-brand-light hover:text-brand-accent dark:hover:text-white transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
            Back to Dashboard
        </a>
    </div>

    @if($trashedGroup)
        {{-- nesen dzēsta grupa – atjaunošana vai tūlītēja iztīrīšana --}}
        <div class="max-w-2xl rounded-xl border border-amber-300 bg-amber-50 p-6 shadow-sm dark:border-amber-500/40 dark:bg-amber-900/20">
            <h3 class="text-sm font-semibold uppercase tracking-[0.14em] text-amber-700 dark:text-amber-300">Scheduled for deletion</h3>
            <p class="mt-2 text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $trashedGroup->name }}</p>
            <p class="mt-1 text-sm text-amber-800/90 dark:text-amber-200/90">
                Deleted on {{ $trashedGroup->deleted_at->format('d.m.Y') }}. It will be permanently removed on
                <span class="font-semibold">{{ $trashedGroup->purgeAt()->format('d.m.Y') }}</span>
                ({{ $trashedGroup->purgeAt()->diffForHumans() }}).
            </p>
            <p class="mt-2 text-sm text-amber-800/90 dark:text-amber-200/90">
                Restoring brings back every costume, item, assignment and student exactly as they were.
                Students whose only group was this one can log in again once you restore.
            </p>

            <div class="mt-5 flex flex-wrap items-center gap-3">
                <form method="POST" action="{{ route('admin.group.restore') }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center rounded-lg border border-brand-primary/20 bg-brand-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-accent focus:outline-none focus:ring-2 focus:ring-brand-secondary/50">
                        Restore group
                    </button>
                </form>
            </div>

            <details class="mt-5 border-t border-amber-300/60 pt-4 dark:border-amber-500/30">
                <summary class="cursor-pointer text-xs font-semibold text-amber-700 dark:text-amber-300">Delete permanently now</summary>
                <p class="mt-2 text-sm text-amber-800/90 dark:text-amber-200/90">
                    This skips the recovery window. Costumes, items, QR codes, history and any deactivated
                    student accounts are erased immediately and cannot be recovered.
                </p>
                <form method="POST" action="{{ route('admin.group.force-destroy') }}" class="mt-3 flex flex-wrap items-end gap-3"
                    onsubmit="return confirm('Permanently delete “{{ $trashedGroup->name }}” and all its data? This cannot be undone.');">
                    @csrf
                    @method('DELETE')
                    <div>
                        <label for="force_password" class="block text-xs font-medium text-amber-800 dark:text-amber-200">Your password</label>
                        <input type="password" name="password" id="force_password" required autocomplete="current-password"
                            class="mt-1 w-56 rounded-lg border-amber-300 px-3 py-2 text-sm text-gray-800 shadow-sm focus:border-brand-primary focus:ring-brand-secondary/50 dark:border-amber-500/40 dark:bg-gray-900 dark:text-gray-200">
                    </div>
                    <button type="submit" class="inline-flex items-center rounded-lg border border-red-300 bg-red-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-400">
                        Delete permanently
                    </button>
                </form>
                @error('password')
                    <p class="mt-2 text-sm text-red-700 dark:text-red-300">{{ $message }}</p>
                @enderror
            </details>
        </div>
    @elseif($group)
        {{-- pārsaukšana --}}
        <div class="max-w-2xl rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Group name</h3>
            <form method="POST" action="{{ route('admin.group.update') }}" class="mt-3 flex flex-wrap items-end gap-3">
                @csrf
                @method('PATCH')
                <div class="min-w-0 flex-1">
                    <input type="text" name="name" value="{{ old('name', $group->name) }}" required autocomplete="off"
                        class="w-full rounded-lg border-gray-300 px-3 py-2.5 text-gray-800 shadow-sm focus:border-brand-primary focus:ring-brand-secondary/50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                    @error('name')
                        <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" class="inline-flex items-center rounded-lg border border-brand-primary/20 bg-brand-primary px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-accent focus:outline-none focus:ring-2 focus:ring-brand-secondary/50">
                    Save
                </button>
            </form>

            <dl class="mt-6 grid grid-cols-3 gap-4 border-t border-gray-100 pt-5 text-center dark:border-gray-700/60">
                <div>
                    <dt class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Students</dt>
                    <dd class="mt-1 text-xl font-semibold text-gray-900 dark:text-gray-100">{{ $stats['students'] }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Costumes</dt>
                    <dd class="mt-1 text-xl font-semibold text-gray-900 dark:text-gray-100">{{ $stats['costumes'] }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Items</dt>
                    <dd class="mt-1 text-xl font-semibold text-gray-900 dark:text-gray-100">{{ $stats['items'] }}</dd>
                </div>
            </dl>
        </div>

        {{-- bīstamā zona --}}
        <div class="mt-6 max-w-2xl rounded-xl border border-red-200 bg-white p-6 shadow-sm dark:border-red-500/30 dark:bg-gray-800">
            <h3 class="text-sm font-semibold text-red-700 dark:text-red-400">Delete this group</h3>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                The group is hidden immediately and kept for {{ \App\Models\Group::PURGE_AFTER_DAYS }} days so you can
                restore it. After that, all {{ $stats['costumes'] }} costumes, {{ $stats['items'] }} items, their QR
                codes and history are permanently erased.
            </p>
            @if($stats['items_out'] > 0)
                <p class="mt-3 rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-sm text-amber-800 dark:border-amber-500/40 dark:bg-amber-900/20 dark:text-amber-300">
                    {{ $stats['items_out'] }} item(s) are still checked out. Get them back before deleting the group.
                </p>
            @endif
            <a href="{{ route('admin.group.delete') }}"
                class="mt-4 inline-flex items-center rounded-lg border border-red-300 bg-red-50 px-4 py-2 text-sm font-semibold text-red-700 transition hover:bg-red-100 dark:border-red-500/40 dark:bg-red-900/20 dark:text-red-300 dark:hover:bg-red-900/40">
                Delete group…
            </a>
        </div>
    @else
        <p class="rounded-xl border border-dashed border-gray-300 px-4 py-8 text-center text-sm text-gray-500 dark:border-gray-600 dark:text-gray-400">
            You don't have a group yet.
        </p>
    @endif
</x-app-layout>
