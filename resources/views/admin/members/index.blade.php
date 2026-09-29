<x-app-layout>
    {{-- studentu saraksts --}}
    <x-slot name="header">
        <x-page-header :eyebrow="auth()->user()->adminGroups()->value('name')" title="Members" subtitle="Your students, their sets and invites.">
            <x-slot:actions>
                <a href="{{ route('admin.members.create') }}"
                    class="ui-btn ui-btn-sm">
                    Add students
                </a>
            </x-slot:actions>
        </x-page-header>
    </x-slot>

    @php
        $failedInvites = $members->whereNotNull('invite_email_failed_at');
        $setNames = $sets->pluck('name', 'id');
    @endphp

    {{-- kopsavilkums, ja kādam uzaicinājuma e-pasts nav nosūtīts --}}
    @if($failedInvites->isNotEmpty())
        <div class="ui-alert ui-alert-warn mb-4">
            {{ $failedInvites->count() }} invite {{ Str::plural('email', $failedInvites->count()) }} could not be delivered — look for the “Invite not delivered” tag below and resend.
        </div>
    @endif

    {{-- komplekta maiņa atzīmētajiem studentiem; izvēles rūtiņas tabulā piesaistītas šai formai ar form="bulk-set" --}}
    @if($members->isNotEmpty())
        <form id="bulk-set" method="POST" action="{{ route('admin.members.set') }}"
            class="mb-4 flex flex-wrap items-center gap-3 rounded-[14px] border border-line bg-surface px-4 py-3 text-sm">
            @csrf
            @method('PATCH')
            @if($sets->isEmpty())
                <span class="text-ink-muted">
                    Sort students into sets like “Girls” and “Boys” so concert readiness knows who needs which costumes.
                    <a href="{{ route('admin.group.settings') }}#sets" class="font-semibold text-brand hover:underline">Create sets</a>
                </span>
            @else
                <span class="font-medium text-ink-muted">Ticked students:</span>
                <select name="costume_set_id" class="rounded-lg border-line-strong bg-paper py-1.5 text-sm text-ink focus:border-brand focus:ring-brand/20">
                    @foreach($sets as $set)
                        <option value="{{ $set->id }}">Move to “{{ $set->name }}”</option>
                    @endforeach
                    <option value="">Remove from set</option>
                </select>
                <button type="submit" class="ui-btn ui-btn-sm">
                    Change set
                </button>
                @error('user_ids')
                    <span class="text-red-600 dark:text-red-400">{{ $message }}</span>
                @enderror
            @endif
        </form>
    @endif

    <div class="overflow-hidden rounded-[14px] border border-line bg-surface">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-line text-ink-soft">
                <tr>
                    @if($sets->isNotEmpty())
                        <th class="w-10 px-4 py-3">
                            <input type="checkbox" aria-label="Tick all students" x-data
                                x-on:change="document.querySelectorAll('input[form=bulk-set][name^=user_ids]').forEach(cb => cb.checked = $el.checked)"
                                class="rounded border-line-strong text-brand focus:ring-brand/30">
                        </th>
                    @endif
                    <th class="px-4 py-3 font-mono text-[11.5px] font-normal uppercase tracking-[0.1em]">Name</th>
                    <th class="px-4 py-3 font-mono text-[11.5px] font-normal uppercase tracking-[0.1em]">Email</th>
                    @if($sets->isNotEmpty())
                        <th class="px-4 py-3 font-mono text-[11.5px] font-normal uppercase tracking-[0.1em]">Set</th>
                    @endif
                    <th class="px-4 py-3 font-mono text-[11.5px] font-normal uppercase tracking-[0.1em]">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($members as $member)
                    <tr class="border-t border-line">
                        @if($sets->isNotEmpty())
                            <td class="px-4 py-3">
                                <input type="checkbox" form="bulk-set" name="user_ids[]" value="{{ $member->id }}" aria-label="Tick {{ $member->name }}"
                                    class="rounded border-line-strong text-brand focus:ring-brand/30">
                            </td>
                        @endif
                        <td class="px-4 py-3 text-ink">
                            {{ $member->name }}
                            @if($member->invite_email_failed_at)
                                <span class="ui-chip ui-chip-late ml-1.5">
                                    Invite not delivered
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-ink-muted">{{ $member->email }}</td>
                        @if($sets->isNotEmpty())
                            <td class="px-4 py-3 text-ink-muted">
                                {{ $setNames[$member->pivot->costume_set_id] ?? '—' }}
                            </td>
                        @endif
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap items-center gap-2">
                            <a href="{{ route('admin.members.show', $member) }}"
                                class="ui-btn-ghost ui-btn-sm">
                                View
                            </a>

                            @if($member->invite_email_failed_at)
                                <form action="{{ route('admin.members.resend-invite', $member) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="ui-btn-ghost ui-btn-sm">
                                        Resend invite
                                    </button>
                                </form>
                            @endif

                            @php
                                // skolotāju un vairāku grupu dalībniekus tikai atsaistām no šīs grupas, nevis dzēšam kontu
                                $justRemoves = $member->hasRole('admin') || $member->member_groups_count > 1;
                            @endphp
                            <form action="{{ route('admin.members.destroy', $member) }}"
                                method="POST">
                                @csrf
                                @method('DELETE')
                                @if($justRemoves)
                                    <button type="submit"
                                            onclick="return confirm('Remove “{{ $member->name }}” from your group? Their account is kept{{ $member->hasRole('admin') ? ' — they’re a teacher elsewhere' : ' — they’re still in other groups' }}.')"
                                            class="ui-btn-ghost ui-btn-sm">
                                        Remove
                                    </button>
                                @else
                                    <button type="submit"
                                            onclick="return confirm('Delete this member? The account can still be restored for 30 days.')"
                                            class="ui-btn-danger-solid ui-btn-sm">
                                        Delete
                                    </button>
                                @endif
                            </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $sets->isNotEmpty() ? 5 : 3 }}" class="px-4 py-6 text-center text-ink-soft">
                            No members found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($trashedMembers->isNotEmpty())
        {{-- nesen izņemti dalībnieki – vēl var atjaunot 30 dienu laikā --}}
        <div class="ui-alert ui-alert-warn mt-6 p-6">
            <h3 class="font-mono text-[11.5px] tracking-[0.1em] uppercase text-rust">Recently removed</h3>
            <p class="mt-1 text-sm text-rust">
                These accounts were deleted because this was their only group. They can still be restored.
            </p>

            <ul class="mt-4 space-y-3">
                @foreach($trashedMembers as $member)
                    <li class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-rust/30 bg-surface px-4 py-3">
                        <div>
                            <p class="font-semibold text-ink">{{ $member->name }}</p>
                            <p class="ui-help">
                                {{ $member->email }} &middot; deleted {{ $member->deleted_at->format('d.m.Y') }},
                                purged {{ $member->deleted_at->copy()->addDays(\App\Models\Group::PURGE_AFTER_DAYS)->diffForHumans() }}
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <form method="POST" action="{{ route('admin.members.restore', $member) }}">
                                @csrf
                                <button type="submit" class="ui-btn ui-btn-sm">
                                    Restore
                                </button>
                            </form>

                            <button type="button" x-data x-on:click.prevent="$dispatch('open-modal', 'confirm-force-destroy-{{ $member->id }}')"
                                class="ui-btn-danger ui-btn-sm">
                                Delete permanently
                            </button>

                            <x-modal name="confirm-force-destroy-{{ $member->id }}" :show="$errors->{'forceDestroy'.$member->id}->isNotEmpty()" focusable>
                                <form method="POST" action="{{ route('admin.members.force-destroy', $member) }}" class="p-6">
                                    @csrf
                                    @method('DELETE')
                                    <h2 class="ui-heading">
                                        Permanently delete “{{ $member->name }}”?
                                    </h2>
                                    <p class="mt-1 text-sm text-ink-muted">
                                        This skips the recovery window and cannot be undone. Enter your password to confirm.
                                    </p>

                                    <div class="mt-6">
                                        <x-input-label for="force_password_{{ $member->id }}" value="Your password" class="sr-only" />
                                        <x-password-input id="force_password_{{ $member->id }}" name="password" class="ui-input mt-1 block w-full"
                                            placeholder="Your password" autocomplete="current-password" />
                                        <x-input-error :messages="$errors->{'forceDestroy'.$member->id}->get('password')" class="mt-2" />
                                    </div>

                                    <div class="mt-6 flex justify-end gap-3">
                                        <button type="button" x-on:click="$dispatch('close')"
                                            class="ui-btn-ghost">
                                            Cancel
                                        </button>
                                        <button type="submit"
                                            class="ui-btn-danger-solid">
                                            Delete permanently
                                        </button>
                                    </div>
                                </form>
                            </x-modal>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</x-app-layout>
