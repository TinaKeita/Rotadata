<x-app-layout>
    {{-- tērpa nosaukuma rediģēšana --}}
    <x-slot name="header">
        <x-page-header title="Edit costume" subtitle="Change the costume name." />
    </x-slot>

    <div class="mb-4">
        <a href="{{ route('admin.costumes.show', $costume) }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-brand-secondary dark:text-brand-light hover:text-brand-accent dark:hover:text-white transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
            Back to inventory
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

        <form method="POST" action="{{ route('admin.costumes.update', $costume) }}" class="space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Name</label>
                <input type="text" name="name" id="name" value="{{ old('name', $costume->name) }}" required autocomplete="off"
                    class="mt-1.5 w-full rounded-lg border-gray-300 px-3 py-2.5 text-gray-800 shadow-sm focus:border-brand-primary focus:ring-brand-secondary/50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
            </div>

            <p class="text-xs text-gray-500 dark:text-gray-400">
                Item codes (like <span class="font-semibold">{{ $costume->code_prefix }}-01</span>) and printed QR labels stay the same when you rename.
            </p>

            <button class="inline-flex items-center rounded-lg border border-brand-primary/20 bg-brand-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-accent focus:outline-none focus:ring-2 focus:ring-brand-secondary/50">
                Save
            </button>
        </form>
    </div>
</x-app-layout>
