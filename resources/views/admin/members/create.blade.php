<x-app-layout>
    {{-- pievieno vienu vai vairākus studentus vienā reizē --}}
    <x-slot name="header">
        <x-page-header title="Add Members" subtitle="Add one or more students to your group — new or existing." />
    </x-slot>

    <div class="mb-4">
        <a href="{{ route('admin.members.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-brand-secondary dark:text-brand-light hover:text-brand-accent dark:hover:text-white transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
            Back to Members
        </a>
    </div>

    <div class="max-w-2xl rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        {{-- validācijas kļūdas --}}
        @if ($errors->any())
            <div class="mb-5 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-700 dark:bg-red-900/30 dark:text-red-300">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.members.store') }}" class="space-y-5"
            x-data="{ rows: @js(old('members', [['name' => '', 'email' => '']])) }">
            @csrf

            <div class="space-y-3">
                <template x-for="(row, index) in rows" :key="index">
                    <div class="flex flex-wrap items-start gap-3 rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                        <div class="min-w-0 flex-1">
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-300">Name</label>
                            <input type="text" :name="`members[${index}][name]`" x-model="row.name" required autocomplete="off"
                                class="mt-1 w-full rounded-lg border-gray-300 px-3 py-2 text-sm text-gray-800 shadow-sm focus:border-brand-primary focus:ring-brand-secondary/50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                        </div>
                        <div class="min-w-0 flex-1">
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-300">Email</label>
                            <input type="email" :name="`members[${index}][email]`" x-model="row.email" required autocomplete="off"
                                class="mt-1 w-full rounded-lg border-gray-300 px-3 py-2 text-sm text-gray-800 shadow-sm focus:border-brand-primary focus:ring-brand-secondary/50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                        </div>
                        <button type="button" x-show="rows.length > 1" @click="rows.splice(index, 1)"
                            class="mt-6 inline-flex shrink-0 items-center rounded-lg border border-red-300 bg-red-50 px-2.5 py-2 text-xs font-semibold text-red-700 transition hover:bg-red-100 dark:border-red-500/40 dark:bg-red-900/20 dark:text-red-300 dark:hover:bg-red-900/40">
                            Remove
                        </button>
                    </div>
                </template>
            </div>

            <button type="button" @click="rows.push({ name: '', email: '' })"
                class="inline-flex items-center rounded-lg border border-brand-primary/25 bg-brand-light/50 px-3 py-1.5 text-xs font-semibold text-brand-accent transition hover:bg-brand-light/75 dark:border-brand-secondary/35 dark:bg-darkbrand-light/45 dark:text-brand-light">
                + Add another student
            </button>

            <p class="text-xs text-gray-500 dark:text-gray-400">
                If someone already uses Rotadata, they're just added to your group and emailed — no new password, and the name above is ignored for them.
            </p>

            <button type="submit" class="inline-flex items-center gap-1 rounded-lg border border-brand-primary/20 bg-brand-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-accent focus:outline-none focus:ring-2 focus:ring-brand-secondary/50">
                <span>Add</span>
                <span x-text="`${rows.length} ${rows.length === 1 ? 'Member' : 'Members'}`"></span>
            </button>
        </form>
    </div>
</x-app-layout>
