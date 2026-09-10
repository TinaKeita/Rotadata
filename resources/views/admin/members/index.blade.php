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
                        <td class="px-4 py-3 text-gray-800 dark:text-gray-100">{{ $member->name }}</td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $member->email }}</td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap items-center gap-2">
                            <a href="{{ route('admin.members.show', $member) }}"
                                class="inline-flex items-center rounded-lg border border-brand-primary/25 bg-brand-light/50 px-3 py-1.5 text-xs font-semibold text-brand-accent transition hover:bg-brand-light/75 dark:border-brand-secondary/35 dark:bg-darkbrand-light/45 dark:text-brand-light">
                                View
                            </a>

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
