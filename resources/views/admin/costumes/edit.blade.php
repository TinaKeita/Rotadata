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

        <form method="POST" action="{{ route('admin.costumes.update', $costume) }}" class="space-y-5" enctype="multipart/form-data">
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

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Photo</label>

                @if($costume->image)
                    <div class="mt-2 flex items-center gap-3">
                        <img src="{{ $costume->imageUrl() }}" alt="" class="h-16 w-16 rounded-lg border border-gray-200 object-cover dark:border-gray-700">
                        <label class="inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                            <input type="checkbox" name="remove_image" value="1" class="rounded border-gray-300 text-red-600 focus:ring-red-400 dark:border-gray-600 dark:bg-gray-900">
                            Remove photo
                        </label>
                    </div>
                @endif

                <input type="file" name="image" id="image" accept="image/png,image/jpeg,image/webp"
                    class="mt-2 block w-full text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-light/60 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-brand-accent hover:file:bg-brand-light dark:text-gray-300 dark:file:bg-darkbrand-light/45 dark:file:text-brand-light">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Uploading a new photo replaces the current one. JPG, PNG or WEBP, up to 4 MB.</p>
            </div>

            <button class="inline-flex items-center rounded-lg border border-brand-primary/20 bg-brand-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-accent focus:outline-none focus:ring-2 focus:ring-brand-secondary/50">
                Save
            </button>
        </form>
    </div>
</x-app-layout>
