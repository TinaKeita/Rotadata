<x-app-layout>
    {{-- tērpu vienību lapa --}}
    <x-slot name="header">
        <x-page-header :title="$costume->name.' Inventory'"
            :subtitle="'Code prefix '.$costume->code_prefix.' · track assignments and download QR codes.'">
            <x-slot:actions>
                <a href="{{ route('admin.costumes.edit', $costume) }}"
                    class="inline-flex items-center rounded-lg border border-brand-primary/25 bg-brand-light/50 px-3 py-1.5 text-xs font-semibold text-brand-accent transition hover:bg-brand-light/75 dark:border-brand-secondary/35 dark:bg-darkbrand-light/45 dark:text-brand-light">
                    Edit name
                </a>
                <a href="{{ route('admin.costumes.labels', $costume) }}" target="_blank" rel="noopener"
                    class="inline-flex items-center rounded-lg border border-brand-primary/25 bg-brand-light/50 px-3 py-1.5 text-xs font-semibold text-brand-accent transition hover:bg-brand-light/75 dark:border-brand-secondary/35 dark:bg-darkbrand-light/45 dark:text-brand-light">
                    Print label sheet
                </a>
            </x-slot:actions>
        </x-page-header>
    </x-slot>

    <div class="mb-4">
        <a href="{{ route('admin.costumes.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-brand-secondary dark:text-brand-light hover:text-brand-accent dark:hover:text-white transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
            Back to Costumes
        </a>
    </div>

    {{-- papildu vienību pievienošana --}}
    <div class="mb-6 rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <form method="POST" action="{{ route('admin.costumes.items.add', $costume) }}" class="flex flex-wrap items-end gap-3">
            @csrf
            <div>
                <label for="count" class="block text-xs font-medium text-gray-600 dark:text-gray-300">Add items</label>
                <input type="number" name="count" id="count" value="1" min="1" max="100" required
                    class="mt-1 w-24 rounded-lg border-gray-300 px-3 py-2 text-sm text-gray-800 shadow-sm focus:border-brand-primary focus:ring-brand-secondary/50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
            </div>
            <button type="submit" class="inline-flex items-center rounded-lg border border-brand-primary/20 bg-brand-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-accent focus:outline-none focus:ring-2 focus:ring-brand-secondary/50">
                Add
            </button>
            <p class="text-xs text-gray-500 dark:text-gray-400">Currently {{ $items->count() }} {{ Str::plural('item', $items->count()) }}. New ones continue the {{ $costume->code_prefix }} code series.</p>
        </form>
    </div>

    <div class="space-y-3">
        @foreach($items as $item)
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="mb-3 flex items-start justify-between gap-3">
                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                        {{ $item->code }}
                        <span class="ml-1 font-normal text-xs text-gray-400 dark:text-gray-500">#{{ $item->id }}</span>
                    </p>

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
                    <figure class="text-center">
                        {!! QrCode::size(120)->generate(url('/scan/'.$item->qr_code)) !!}
                        <figcaption class="mt-1 text-xs font-semibold tracking-wide text-gray-700 dark:text-gray-300">{{ $item->code }}</figcaption>
                    </figure>

                    <a href="{{ route('qr.download', $item->qr_code) }}"
                        class="inline-flex items-center rounded-lg border border-brand-primary/25 bg-brand-light/50 px-3 py-1.5 text-xs font-semibold text-brand-accent transition hover:bg-brand-light/75 dark:border-brand-secondary/35 dark:bg-darkbrand-light/45 dark:text-brand-light">
                        Download
                    </a>

                    <form method="POST" action="{{ route('admin.costumes.items.regenerate-qr', $item) }}"
                        onsubmit="return confirm('Generate a new QR code for {{ $item->code }}? The old printed label will stop working and must be replaced.');">
                        @csrf
                        <button type="submit" class="inline-flex items-center rounded-lg border border-amber-300 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700 transition hover:bg-amber-100 dark:border-amber-500/40 dark:bg-amber-900/20 dark:text-amber-300 dark:hover:bg-amber-900/40">
                            Regenerate QR
                        </button>
                    </form>

                    @unless($item->assigned_to)
                        <form method="POST" action="{{ route('admin.costumes.items.destroy', $item) }}"
                            onsubmit="return confirm('Delete item {{ $item->code }}? This cannot be undone.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex items-center rounded-lg border border-red-300 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 transition hover:bg-red-100 dark:border-red-500/40 dark:bg-red-900/20 dark:text-red-300 dark:hover:bg-red-900/40">
                                Delete item
                            </button>
                        </form>
                    @endunless
                </div>

                @if($item->assignments->isNotEmpty())
                    <details class="mt-3 border-t border-gray-100 pt-3 dark:border-gray-700/60">
                        <summary class="cursor-pointer text-xs font-semibold text-brand-secondary dark:text-brand-light">
                            History ({{ $item->assignments->count() }})
                        </summary>
                        <ul class="mt-2 space-y-2">
                            @foreach($item->assignments as $log)
                                <li class="text-xs text-gray-600 dark:text-gray-300">
                                    <span class="font-semibold text-gray-800 dark:text-gray-100">{{ $log->user_name }}</span>
                                    <span class="text-gray-400"> · </span>
                                    {{ $log->assigned_at->format('d.m.Y') }}
                                    &rarr;
                                    @if($log->returned_at)
                                        {{ $log->returned_at->format('d.m.Y') }}
                                        <span class="text-gray-400">
                                            @if($log->return_note === 'admin')
                                                (taken back by {{ $log->returnedBy->name ?? 'teacher' }})
                                            @elseif($log->return_note === 'transfer')
                                                (handed over to {{ $log->returnedBy->name ?? 'another member' }})
                                            @elseif($log->return_note === 'removed')
                                                (member removed)
                                            @else
                                                (returned by student)
                                            @endif
                                        </span>
                                    @else
                                        <span class="font-medium text-amber-600 dark:text-amber-400">still out</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </details>
                @endif
            </div>
        @endforeach
    </div>

    {{-- bīstamā zona: visa tērpa dzēšana --}}
    <div class="mt-8 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-red-200 bg-red-50/60 p-4 dark:border-red-500/30 dark:bg-red-900/10">
        <div>
            <p class="text-sm font-semibold text-red-800 dark:text-red-300">Delete this costume</p>
            <p class="text-xs text-red-700/80 dark:text-red-300/70">Removes the costume and all its items and QR codes. This cannot be undone.</p>
        </div>
        <form action="{{ route('admin.costumes.destroy', $costume->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this costume?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="inline-flex items-center rounded-lg border border-red-300 bg-white px-3 py-1.5 text-xs font-semibold text-red-700 transition hover:bg-red-100 dark:border-red-500/40 dark:bg-transparent dark:text-red-300 dark:hover:bg-red-900/30">
                Delete Costume
            </button>
        </form>
    </div>
</x-app-layout>


