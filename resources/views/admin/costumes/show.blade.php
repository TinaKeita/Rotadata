<x-app-layout>
    {{-- tērpu vienību lapa --}}
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="text-2xl font-semibold text-brand-accent dark:text-brand-light leading-tight">{{ $costume->name }} Inventory</h2>
            <p class="text-sm text-gray-600 dark:text-gray-300">Track item assignment and download QR codes.</p>
        </div>
    </x-slot>

    <div class="mb-4">
        <a href="{{ route('admin.costumes.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-brand-secondary dark:text-brand-light hover:text-brand-accent dark:hover:text-white transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
            Back to Costumes
        </a>
    </div>

    <div class="mb-6">
        <form action="{{ route('admin.costumes.destroy', $costume->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this costume?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="inline-flex items-center rounded-lg border border-brand-primary/20 bg-brand-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-accent focus:outline-none focus:ring-2 focus:ring-brand-secondary/50 dark:border-darkbrand-accent dark:bg-darkbrand-secondary dark:hover:bg-darkbrand-accent">
                Delete Costume
            </button>
        </form>
    </div>

    <div class="space-y-3">
        @foreach($items as $item)
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="mb-3 flex items-start justify-between gap-3">
                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">Item #{{ $item->id }}</p>

                    @if($item->assigned_to)
                        <div class="flex items-center gap-2">
                            <span class="rounded-full border border-amber-300 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 dark:border-amber-500/40 dark:bg-amber-900/20 dark:text-amber-300">
                                Assigned to {{ $item->user->name }}
                            </span>
                            <form method="POST" action="{{ route('admin.costumes.items.unassign', $item) }}">
                                @csrf
                                <button type="submit" class="inline-flex items-center rounded-lg border border-red-300 bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700 transition hover:bg-red-100 dark:border-red-500/40 dark:bg-red-900/20 dark:text-red-300 dark:hover:bg-red-900/40">
                                    Unassign
                                </button>
                            </form>
                        </div>
                    @else
                        <span class="rounded-full border border-emerald-300 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 dark:border-emerald-500/40 dark:bg-emerald-900/20 dark:text-emerald-300">
                            Available
                        </span>
                    @endif
                </div>

                <div class="flex flex-wrap items-end gap-4">
                    {!! QrCode::size(120)->generate(url('/scan/'.$item->qr_code)) !!}

                    <a href="{{ route('qr.download', $item->qr_code) }}"
                        class="inline-flex items-center rounded-lg border border-brand-primary/25 bg-brand-light/50 px-3 py-1.5 text-xs font-semibold text-brand-accent transition hover:bg-brand-light/75 dark:border-brand-secondary/35 dark:bg-darkbrand-light/45 dark:text-brand-light">
                        Download
                    </a>
                </div>
            </div>
        @endforeach
    </div>
</x-app-layout>


