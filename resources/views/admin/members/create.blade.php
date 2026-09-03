<x-app-layout>
    {{-- pievieno jaunu studentu --}}
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="text-2xl font-semibold text-brand-accent dark:text-brand-light leading-tight">Create New Member</h2>
            <p class="text-sm text-gray-600 dark:text-gray-300">Add a student account for your group.</p>
        </div>
    </x-slot>

    <div class="mb-4">
        <a href="{{ route('admin.members.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-brand-secondary dark:text-brand-light hover:text-brand-accent dark:hover:text-white transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
            Back to Members
        </a>
    </div>

    {{-- success message --}}
    @if (session('success'))
        <div class="max-w-2xl mb-4 rounded-lg border border-green-300 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-700 dark:bg-green-900/30 dark:text-green-300">
            {{ session('success') }}
        </div>
    @endif

    <div class="max-w-2xl rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        {{-- validation errors --}}
        @if ($errors->any())
            <div class="mb-5 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-700 dark:bg-red-900/30 dark:text-red-300">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.members.store') }}" class="space-y-5">
            @csrf

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Name</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" class="mt-1.5 w-full rounded-lg border-gray-300 px-3 py-2.5 text-gray-800 shadow-sm focus:border-brand-primary focus:ring-brand-secondary/50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 @error('name') border-red-500 @enderror" required>
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" class="mt-1.5 w-full rounded-lg border-gray-300 px-3 py-2.5 text-gray-800 shadow-sm focus:border-brand-primary focus:ring-brand-secondary/50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 @error('email') border-red-500 @enderror" required>
            </div>

            <button type="submit" class="inline-flex items-center rounded-lg border border-brand-primary/20 bg-brand-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-accent focus:outline-none focus:ring-2 focus:ring-brand-secondary/50">
                Create Member
            </button>
        </form>
    </div>
</x-app-layout>
