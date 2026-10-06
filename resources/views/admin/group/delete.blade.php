<x-app-layout>
    {{-- grupas dzēšanas apstiprinājums: jāievada grupas nosaukums un parole --}}
    <x-slot name="header">
        <x-page-header :eyebrow="$group->name" title="Delete group" :subtitle="'Confirm you want to delete “'.$group->name.'”.'" />
    </x-slot>

    <div class="mb-4">
        <a href="{{ route('admin.group.settings') }}" class="ui-back">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
            Back to Group settings
        </a>
    </div>

    <div class="max-w-2xl rounded-[14px] border border-danger/25 bg-surface p-6">

        {{-- kas tiks ietekmēts --}}
        <h3 class="ui-eyebrow">What happens</h3>
        <ul class="mt-3 space-y-2 text-sm text-ink-muted">
            <li>· <span class="font-semibold text-ink">{{ $stats['costumes'] }}</span> costumes and <span class="font-semibold text-ink">{{ $stats['items'] }}</span> items (with QR codes and history) are hidden now, erased on {{ now()->addDays(\App\Models\Group::PURGE_AFTER_DAYS)->format('d.m.Y') }}.</li>
            <li>· <span class="font-semibold text-ink">{{ $stats['students_deactivated'] }}</span> students are only in this group — their accounts are deactivated and they're emailed.</li>
            <li>· <span class="font-semibold text-ink">{{ $stats['students_kept'] }}</span> students are in other groups — their accounts keep working, they're just emailed.</li>
            <li>· You can restore everything from Group settings until the erase date.</li>
        </ul>

        @if($stats['items_out'] > 0)
            <p class="ui-alert ui-alert-warn mt-4 px-3 py-2">
                {{ $stats['items_out'] }} item(s) are still checked out to students. Take them back or have them
                returned before deleting the group.
            </p>
        @endif

        @if ($errors->any())
            <div class="ui-alert ui-alert-error mt-4">
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
                <label for="name" class="ui-label">
                    Type the group name <span class="font-semibold">{{ $group->name }}</span> to confirm
                </label>
                <input type="text" name="name" id="name" required autocomplete="off" autofocus
                    class="mt-1.5 w-full rounded-lg border-line-strong px-3 py-2.5 text-ink focus:border-danger focus:ring-danger/30 bg-paper placeholder:text-ink-soft">
            </div>

            <div>
                <label for="password" class="ui-label">Your password</label>
                <x-password-input name="password" id="password" required autocomplete="current-password"
                    class="mt-1.5 w-full rounded-lg border-line-strong px-3 py-2.5 text-ink focus:border-danger focus:ring-danger/30 bg-paper placeholder:text-ink-soft" />
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" @disabled($stats['items_out'] > 0)
                    class="ui-btn-danger-solid">
                    Delete group
                </button>
                <a href="{{ route('admin.group.settings') }}" class="text-sm font-medium text-ink-muted hover:text-brand">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</x-app-layout>
