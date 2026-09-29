<x-app-layout>
    {{-- grupas iestatījumi: pārsaukšana, dzēšana un nesen dzēstas grupas atjaunošana --}}
    <x-slot name="header">
        <x-page-header :eyebrow="auth()->user()->adminGroups()->value('name')" title="Group settings" subtitle="Rename your group, or delete it with a 30-day recovery window." />
    </x-slot>

    <div class="mb-4">
        <a href="{{ route('dashboard') }}" class="ui-back">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
            Back to Dashboard
        </a>
    </div>

    @if($trashedGroup)
        {{-- nesen dzēsta grupa – atjaunošana vai tūlītēja iztīrīšana --}}
        <div class="ui-alert ui-alert-warn max-w-2xl p-6">
            <h3 class="font-mono text-[11.5px] tracking-[0.1em] uppercase text-rust">Scheduled for deletion</h3>
            <p class="mt-2 text-lg font-semibold text-ink">{{ $trashedGroup->name }}</p>
            <p class="mt-1 text-sm text-rust">
                Deleted on {{ $trashedGroup->deleted_at->format('d.m.Y') }}. It will be permanently removed on
                <span class="font-semibold">{{ $trashedGroup->purgeAt()->format('d.m.Y') }}</span>
                ({{ $trashedGroup->purgeAt()->diffForHumans() }}).
            </p>
            <p class="mt-2 text-sm text-rust">
                Restoring brings back every costume, item, assignment and student exactly as they were.
                Students whose only group was this one can log in again once you restore.
            </p>

            <div class="mt-5 flex flex-wrap items-center gap-3">
                <form method="POST" action="{{ route('admin.group.restore') }}">
                    @csrf
                    <button type="submit" class="ui-btn">
                        Restore group
                    </button>
                </form>
            </div>

            <details class="mt-5 border-t border-rust/30 pt-4">
                <summary class="cursor-pointer text-xs font-semibold text-rust">Delete permanently now</summary>
                <p class="mt-2 text-sm text-rust">
                    This skips the recovery window. Costumes, items, QR codes, history and any deactivated
                    student accounts are erased immediately and cannot be recovered.
                </p>
                <form method="POST" action="{{ route('admin.group.force-destroy') }}" class="mt-3 flex flex-wrap items-end gap-3"
                    onsubmit="return confirm('Permanently delete “{{ $trashedGroup->name }}” and all its data? This cannot be undone.');">
                    @csrf
                    @method('DELETE')
                    <div>
                        <label for="force_password" class="ui-label">Your password</label>
                        <x-password-input name="password" id="force_password" required autocomplete="current-password"
                            class="ui-input mt-1 w-56 !py-2 !text-sm" />
                    </div>
                    <button type="submit" class="ui-btn-danger-solid">
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
        <div class="ui-card max-w-2xl">
            <h3 class="ui-eyebrow">Group name</h3>
            <form method="POST" action="{{ route('admin.group.update') }}" class="mt-3 flex flex-wrap items-end gap-3">
                @csrf
                @method('PATCH')
                <div class="min-w-0 flex-1">
                    <input type="text" name="name" value="{{ old('name', $group->name) }}" required autocomplete="off"
                        class="w-full rounded-lg border-line-strong px-3 py-2.5 text-ink focus:border-brand focus:ring-brand/30 bg-paper placeholder:text-ink-soft">
                    @error('name')
                        <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" class="ui-btn">
                    Save
                </button>
            </form>

            <dl class="mt-6 grid grid-cols-3 gap-4 border-t border-line-soft pt-5 text-center">
                <div>
                    <dt class="font-mono text-[11.5px] tracking-[0.1em] uppercase text-ink-soft">Students</dt>
                    <dd class="ui-heading text-[26px] mt-1">{{ $stats['students'] }}</dd>
                </div>
                <div>
                    <dt class="font-mono text-[11.5px] tracking-[0.1em] uppercase text-ink-soft">Costumes</dt>
                    <dd class="ui-heading text-[26px] mt-1">{{ $stats['costumes'] }}</dd>
                </div>
                <div>
                    <dt class="font-mono text-[11.5px] tracking-[0.1em] uppercase text-ink-soft">Items</dt>
                    <dd class="ui-heading text-[26px] mt-1">{{ $stats['items'] }}</dd>
                </div>
            </dl>
        </div>

        {{-- tērpu komplekti: kārto studentus un tērpus, lai koncerta gatavība zina, kam ko vajag --}}
        <div id="sets" class="ui-card mt-6 max-w-2xl">
            <h3 class="ui-eyebrow">Costume sets</h3>
            <p class="mt-2 text-sm text-ink-muted">
                Sets like “Girls” and “Boys” sort who needs which costumes. A costume without a set is shared and everyone needs it.
                A student is ready for a concert when they hold one item of every shared costume and every costume in their set.
                Sets don't limit scanning.
            </p>

            @if($group->costumeSets->isNotEmpty())
                <ul class="mt-4 space-y-2">
                    @foreach($group->costumeSets as $set)
                        <li class="flex flex-wrap items-center gap-2">
                            <form method="POST" action="{{ route('admin.costume-sets.update', $set) }}" class="flex min-w-0 flex-1 items-center gap-2">
                                @csrf
                                @method('PATCH')
                                <input type="text" name="set_name" value="{{ $set->name }}" required maxlength="60" aria-label="Set name"
                                    class="min-w-0 flex-1 rounded-lg border-line-strong px-3 py-2 text-sm text-ink focus:border-brand focus:ring-brand/30 bg-paper placeholder:text-ink-soft">
                                <button type="submit" class="ui-btn-ghost ui-btn-sm">
                                    Rename
                                </button>
                            </form>
                            <form method="POST" action="{{ route('admin.costume-sets.destroy', $set) }}"
                                onsubmit="return confirm('Delete the set “{{ $set->name }}”? Its costumes become shared and its students will have no set.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="ui-btn-danger ui-btn-sm">
                                    Delete
                                </button>
                            </form>
                            @if($errors->{'costumeSet'.$set->id}->any())
                                <p class="w-full text-sm text-red-600 dark:text-red-400">{{ $errors->{'costumeSet'.$set->id}->first('set_name') }}</p>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif

            <form method="POST" action="{{ route('admin.costume-sets.store') }}" class="mt-4 flex flex-wrap items-center gap-2 border-t border-line-soft pt-4">
                @csrf
                <input type="text" name="set_name" value="{{ old('set_name') }}" required maxlength="60" placeholder="New set, e.g. Girls" aria-label="New set name"
                    class="min-w-0 flex-1 rounded-lg border-line-strong px-3 py-2 text-sm text-ink focus:border-brand focus:ring-brand/30 bg-paper placeholder:text-ink-soft">
                <button type="submit" class="ui-btn">
                    Add set
                </button>
                @if($errors->costumeSet->any())
                    <p class="w-full text-sm text-red-600 dark:text-red-400">{{ $errors->costumeSet->first('set_name') }}</p>
                @endif
            </form>
        </div>

        {{-- sezonas atskaite – vienmēr pieejama, ne tikai sezonas beigās --}}
        <div class="ui-card mt-6 max-w-2xl">
            <h3 class="ui-eyebrow">Season report</h3>
            <p class="mt-2 text-sm text-ink-muted">
                A printable summary of the {{ \App\Support\Season::label() }} season: what's still checked out and
                how each costume was used. Best exported before summer break, but available any time.
            </p>
            <a href="{{ route('admin.season-report.show') }}" target="_blank"
                class="ui-btn mt-4">
                Export season report
            </a>
        </div>

        {{-- bīstamā zona --}}
        <div class="mt-6 max-w-2xl rounded-[14px] border border-red-200 bg-surface p-6 dark:border-red-500/30">
            <h3 class="font-mono text-[11.5px] uppercase tracking-[0.1em] text-red-700 dark:text-red-400">Delete this group</h3>
            <p class="mt-2 text-sm text-ink-muted">
                The group is hidden immediately and kept for {{ \App\Models\Group::PURGE_AFTER_DAYS }} days so you can
                restore it. After that, all {{ $stats['costumes'] }} costumes, {{ $stats['items'] }} items, their QR
                codes and history are permanently erased.
            </p>
            @if($stats['items_out'] > 0)
                <p class="ui-alert ui-alert-warn mt-3 px-3 py-2">
                    {{ $stats['items_out'] }} item(s) are still checked out. Get them back before deleting the group.
                </p>
            @endif
            <a href="{{ route('admin.group.delete') }}"
                class="ui-btn-danger mt-4">
                Delete group…
            </a>
        </div>
    @else
        <p class="rounded-[14px] border border-dashed border-line-strong px-4 py-8 text-center text-sm text-ink-soft">
            You don't have a group yet.
        </p>
    @endif
</x-app-layout>
