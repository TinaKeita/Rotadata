<x-app-layout>
    {{-- pievieno jaunu tērpu --}}
    <x-slot name="header">
        <x-page-header title="Create Costume" subtitle="Add a new costume and generate inventory items." />
    </x-slot>

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

        <form method="POST" action="{{ route('admin.costumes.store') }}" class="space-y-5" enctype="multipart/form-data">
            @csrf

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Name</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" class="mt-1.5 w-full rounded-lg border-gray-300 px-3 py-2.5 text-gray-800 shadow-sm focus:border-brand-primary focus:ring-brand-secondary/50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200" required autocomplete="name">
            </div>

            <div>
                <label for="quantity" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Quantity</label>
                <input type="number" name="quantity" id="quantity" value="{{ old('quantity') }}" class="mt-1.5 w-full rounded-lg border-gray-300 px-3 py-2.5 text-gray-800 shadow-sm focus:border-brand-primary focus:ring-brand-secondary/50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200" required min="1" autocomplete="off">
            </div>

            <div>
                <label for="image" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Photo <span class="font-normal text-gray-400">(optional)</span></label>
                <input type="file" name="image" id="image" accept="image/png,image/jpeg,image/webp"
                    class="mt-1.5 block w-full text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-light/60 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-brand-accent hover:file:bg-brand-light dark:text-gray-300 dark:file:bg-darkbrand-light/45 dark:file:text-brand-light">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Helps members recognize the right costume when scanning. JPG, PNG or WEBP, up to 4 MB.</p>
            </div>

            <button class="inline-flex items-center rounded-lg border border-brand-primary/20 bg-brand-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-accent focus:outline-none focus:ring-2 focus:ring-brand-secondary/50">
                Create
            </button>
        </form>
    </div>
</x-app-layout>
