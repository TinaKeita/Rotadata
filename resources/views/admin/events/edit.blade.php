<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Edit Concert" :subtitle="$event->title" />
    </x-slot>

    <div class="max-w-2xl rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        @if ($errors->any())
            <div class="mb-5 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-700 dark:bg-red-900/30 dark:text-red-300">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.events.update', $event) }}" class="space-y-5">
            @csrf
            @method('PUT')

            @include('admin.events._form')

            <button class="inline-flex items-center rounded-lg border border-brand-primary/20 bg-brand-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-accent focus:outline-none focus:ring-2 focus:ring-brand-secondary/50">
                Save changes
            </button>
        </form>
    </div>
</x-app-layout>
