<x-app-layout>
    {{-- studentu saraksts --}}
    <x-slot name="header">
        <x-page-header title="Members List" subtitle="View and manage student accounts.">
            <x-slot:actions>
                <a href="{{ route('admin.members.create') }}"
                    class="inline-flex items-center rounded-lg border border-brand-primary/20 bg-brand-primary px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-brand-accent focus:outline-none focus:ring-2 focus:ring-brand-secondary/50">
                    + Add Member
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

                            <form action="{{ route('admin.members.destroy', $member) }}"
                                method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        onclick="return confirm('Delete this member?')"
                                        class="inline-flex items-center rounded-lg border border-red-300 bg-red-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-red-500 dark:border-red-400/40 dark:bg-red-700 dark:hover:bg-red-600">
                                    Delete
                                </button>
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
</x-app-layout>
