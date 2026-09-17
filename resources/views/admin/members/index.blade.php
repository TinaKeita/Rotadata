<x-app-layout>
    {{-- studentu saraksts --}}
    <x-slot name="header">
        <x-page-header title="Members List" subtitle="View and manage student accounts.">
            <x-slot:actions>
                <a href="{{ route('admin.members.create') }}"
                    class="inline-flex items-center rounded-lg border border-brand-primary/20 bg-brand-primary px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-brand-accent focus:outline-none focus:ring-2 focus:ring-brand-secondary/50">
                    + Add Members
                </a>
            </x-slot:actions>
        </x-page-header>
    </x-slot>

    @php
        $failedInvites = $members->whereNotNull('invite_email_failed_at');
    @endphp

    {{-- kopsavilkums, ja kādam uzaicinājuma e-pasts nav nosūtīts --}}
    @if($failedInvites->isNotEmpty())
        <div class="mb-4 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-500/40 dark:bg-amber-900/20 dark:text-amber-300">
            {{ $failedInvites->count() }} invite {{ Str::plural('email', $failedInvites->count()) }} could not be delivered — look for the “Invite not delivered” tag below and resend.
        </div>
    @endif

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <table class="w-full text-left text-sm">
            <thead class="bg-brand-light/35 text-gray-700 dark:bg-darkbrand-light/45 dark:text-gray-200">
                <tr>
                    <th class="px-4 py-3 font-semibold">Name</th>
                    <th class="px-4 py-3 font-semibold">Email</th>
                    <th class="px-4 py-3 font-semibold">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($members as $member)
                    <tr class="border-t border-gray-200 dark:border-gray-700">
                        <td class="px-4 py-3 text-gray-800 dark:text-gray-100">
                            {{ $member->name }}
                            @if($member->invite_email_failed_at)
                                <span class="ml-1.5 inline-flex items-center rounded-full border border-amber-300 bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-700 dark:border-amber-500/40 dark:bg-amber-900/20 dark:text-amber-300">
                                    Invite not delivered
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $member->email }}</td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap items-center gap-2">
                            <a href="{{ route('admin.members.show', $member) }}"
                                class="inline-flex items-center rounded-lg border border-brand-primary/25 bg-brand-light/50 px-3 py-1.5 text-xs font-semibold text-brand-accent transition hover:bg-brand-light/75 dark:border-brand-secondary/35 dark:bg-darkbrand-light/45 dark:text-brand-light">
                                View
                            </a>

                            @if($member->invite_email_failed_at)
                                <form action="{{ route('admin.members.resend-invite', $member) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center rounded-lg border border-amber-300 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700 transition hover:bg-amber-100 dark:border-amber-500/40 dark:bg-amber-900/20 dark:text-amber-300 dark:hover:bg-amber-900/40">
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
                                            class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                                        Remove
                                    </button>
                                @else
                                    <button type="submit"
                                            onclick="return confirm('Delete this member? The account can still be restored for 30 days.')"
                                            class="inline-flex items-center rounded-lg border border-red-300 bg-red-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-red-500 dark:border-red-400/40 dark:bg-red-700 dark:hover:bg-red-600">
                                        Delete
                                    </button>
                                @endif
                            </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">
                            No members found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($trashedMembers->isNotEmpty())
        {{-- nesen izņemti dalībnieki – vēl var atjaunot 30 dienu laikā --}}
        <div class="mt-6 rounded-xl border border-amber-300 bg-amber-50 p-6 shadow-sm dark:border-amber-500/40 dark:bg-amber-900/20">
            <h3 class="text-sm font-semibold uppercase tracking-[0.14em] text-amber-700 dark:text-amber-300">Recently removed</h3>
            <p class="mt-1 text-sm text-amber-800/90 dark:text-amber-200/90">
                These accounts were deleted because this was their only group. They can still be restored.
            </p>

            <ul class="mt-4 space-y-3">
                @foreach($trashedMembers as $member)
                    <li class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-amber-300/70 bg-white px-4 py-3 dark:border-amber-500/30 dark:bg-gray-800">
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $member->name }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $member->email }} &middot; deleted {{ $member->deleted_at->format('d.m.Y') }},
                                purged {{ $member->deleted_at->copy()->addDays(\App\Models\Group::PURGE_AFTER_DAYS)->diffForHumans() }}
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <form method="POST" action="{{ route('admin.members.restore', $member) }}">
                                @csrf
                                <button type="submit" class="inline-flex items-center rounded-lg border border-brand-primary/20 bg-brand-primary px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-brand-accent">
                                    Restore
                                </button>
                            </form>

                            <button type="button" x-data x-on:click.prevent="$dispatch('open-modal', 'confirm-force-destroy-{{ $member->id }}')"
                                class="inline-flex items-center rounded-lg border border-red-300 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 transition hover:bg-red-100 dark:border-red-500/40 dark:bg-red-900/20 dark:text-red-300 dark:hover:bg-red-900/40">
                                Delete permanently
                            </button>

                            <x-modal name="confirm-force-destroy-{{ $member->id }}" :show="$errors->{'forceDestroy'.$member->id}->isNotEmpty()" focusable>
                                <form method="POST" action="{{ route('admin.members.force-destroy', $member) }}" class="p-6">
                                    @csrf
                                    @method('DELETE')
                                    <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                        Permanently delete “{{ $member->name }}”?
                                    </h2>
                                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                        This skips the recovery window and cannot be undone. Enter your password to confirm.
                                    </p>

                                    <div class="mt-6">
                                        <x-input-label for="force_password_{{ $member->id }}" value="Your password" class="sr-only" />
                                        <x-text-input id="force_password_{{ $member->id }}" name="password" type="password" class="mt-1 block w-3/4"
                                            placeholder="Your password" autocomplete="current-password" />
                                        <x-input-error :messages="$errors->{'forceDestroy'.$member->id}->get('password')" class="mt-2" />
                                    </div>

                                    <div class="mt-6 flex justify-end gap-3">
                                        <button type="button" x-on:click="$dispatch('close')"
                                            class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                                            Cancel
                                        </button>
                                        <button type="submit"
                                            class="inline-flex items-center rounded-lg border border-red-300 bg-red-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-400">
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
