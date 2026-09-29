<x-app-layout>
    {{-- tērpu vienību lapa --}}
    <x-slot name="header">
        <x-page-header eyebrow="Costume inventory" :title="$costume->name"
            :subtitle="'Code prefix '.$costume->code_prefix.' · track assignments and download QR codes.'">
            <x-slot:actions>
                <a href="{{ route('admin.costumes.edit', $costume) }}"
                    class="ui-btn-ghost ui-btn-sm">
                    Edit costume
                </a>
                <a href="{{ route('admin.costumes.labels', $costume) }}" target="_blank" rel="noopener"
                    class="ui-btn-ghost ui-btn-sm">
                    Print label sheet
                </a>
            </x-slot:actions>
        </x-page-header>
    </x-slot>

    <div class="mb-4">
        <a href="{{ route('admin.costumes.index') }}" class="ui-back">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
            Back to Costumes
        </a>
    </div>

    @if($costume->image)
        {{-- tērpa foto – palīdz dalībniekiem atpazīt pareizo tērpu skenējot --}}
        <div class="mb-6">
            <img src="{{ $costume->imageUrl() }}" alt="{{ $costume->name }}"
                class="h-48 w-full rounded-[14px] border border-line object-cover sm:h-64 sm:w-64">
        </div>
    @endif

    {{-- papildu vienību pievienošana --}}
    <div class="ui-card mb-6">
        <form method="POST" action="{{ route('admin.costumes.items.add', $costume) }}" class="flex flex-wrap items-end gap-3">
            @csrf
            <div>
                <label for="count" class="ui-label">Add items</label>
                <input type="number" name="count" id="count" value="1" min="1" max="100" required
                    class="mt-1 w-24 rounded-lg border-line-strong px-3 py-2 text-sm text-ink focus:border-brand focus:ring-brand/30 bg-paper placeholder:text-ink-soft">
            </div>
            <button type="submit" class="ui-btn">
                Add
            </button>
            <p class="ui-help">Currently {{ $items->count() }} {{ Str::plural('item', $items->count()) }}. New ones continue the {{ $costume->code_prefix }} code series.</p>
        </form>
    </div>

    <div class="space-y-3">
        @foreach($items as $item)
            <div class="ui-card">
                <div class="mb-3 flex items-start justify-between gap-3">
                    <p class="ui-eyebrow">
                        {{ $item->code }}
                        <span class="ml-1 font-normal text-xs text-ink-soft">#{{ $item->id }}</span>
                    </p>

                    @if($item->assigned_to)
                        <div class="flex items-center gap-2">
                            <span class="ui-chip">
                                With {{ $item->user->name }}
                            </span>
                            <form method="POST" action="{{ route('admin.costumes.items.unassign', $item) }}">
                                @csrf
                                <button type="submit" class="ui-btn-danger ui-btn-sm">
                                    Unassign
                                </button>
                            </form>
                        </div>
                    @else
                        <div class="flex flex-wrap items-center justify-end gap-2">
                            <span class="ui-chip ui-chip-good">Available</span>
                            {{-- skolotājs izsniedz vienību pats, bez QR skenēšanas --}}
                            @if($members->isNotEmpty())
                                <form method="POST" action="{{ route('admin.costumes.items.assign', $item) }}" class="flex items-center gap-2">
                                    @csrf
                                    <select name="user_id" required aria-label="Student to assign {{ $item->code }} to"
                                        class="rounded-lg border-line-strong bg-paper py-1.5 text-sm text-ink focus:border-brand focus:ring-brand/20">
                                        <option value="" disabled selected>Assign to…</option>
                                        @foreach($members as $member)
                                            <option value="{{ $member->id }}">{{ $member->name }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="ui-btn ui-btn-sm">Assign</button>
                                </form>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="flex flex-wrap items-end gap-4">
                    <figure class="text-center">
                        {!! QrCode::size(120)->generate(url('/scan/'.$item->qr_code)) !!}
                        <figcaption class="mt-1 text-xs font-semibold tracking-wide text-ink-muted">{{ $item->code }}</figcaption>
                    </figure>

                    <a href="{{ route('qr.download', $item->qr_code) }}"
                        class="ui-btn-ghost ui-btn-sm">
                        Download
                    </a>

                    <form method="POST" action="{{ route('admin.costumes.items.regenerate-qr', $item) }}"
                        onsubmit="return confirm('Generate a new QR code for {{ $item->code }}? The old printed label will stop working and must be replaced.');">
                        @csrf
                        <button type="submit" class="ui-btn-ghost ui-btn-sm">
                            Regenerate QR
                        </button>
                    </form>

                    @unless($item->assigned_to)
                        <form method="POST" action="{{ route('admin.costumes.items.destroy', $item) }}"
                            onsubmit="return confirm('Delete item {{ $item->code }}? This cannot be undone.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="ui-btn-danger ui-btn-sm">
                                Delete item
                            </button>
                        </form>
                    @endunless
                </div>

                @if($item->assignments->isNotEmpty())
                    <details class="mt-3 border-t border-line-soft pt-3">
                        <summary class="cursor-pointer text-xs font-semibold text-brand">
                            History ({{ $item->assignments->count() }})
                        </summary>
                        <ul class="mt-2 space-y-2">
                            @foreach($item->assignments as $log)
                                <li class="text-xs text-ink-muted">
                                    <span class="font-semibold text-ink">{{ $log->user_name }}</span>
                                    {{-- izsniedza skolotājs, nevis students noskenēja pats --}}
                                    @if($log->assigned_by && $log->assigned_by !== $log->user_id)
                                        <span class="text-ink-soft">(given by {{ $log->assignedBy->name ?? 'teacher' }})</span>
                                    @endif
                                    <span class="text-ink-soft"> · </span>
                                    {{ $log->assigned_at->format('d.m.Y') }}
                                    &rarr;
                                    @if($log->returned_at)
                                        {{ $log->returned_at->format('d.m.Y') }}
                                        <span class="text-ink-soft">
                                            @if($log->return_note === 'admin')
                                                (taken back by {{ $log->returnedBy->name ?? 'teacher' }})
                                            @elseif($log->return_note === 'transfer')
                                                (handed over to {{ $log->returnedBy->name ?? 'another member' }})
                                            @elseif($log->return_note === 'removed')
                                                (member removed)
                                            @elseif($log->return_note === 'left_group')
                                                (left the group)
                                            @else
                                                (returned by student)
                                            @endif
                                        </span>
                                    @else
                                        <span class="font-medium text-rust">still out</span>
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
    <div class="mt-8 flex flex-wrap items-center justify-between gap-3 rounded-[14px] border border-red-200 bg-red-50/60 p-4 dark:border-red-500/30 dark:bg-red-900/10">
        <div>
            <p class="text-sm font-semibold text-red-800 dark:text-red-300">Delete this costume</p>
            <p class="text-xs text-red-700/80 dark:text-red-300/70">Removes the costume and all its items and QR codes. This cannot be undone.</p>
        </div>
        <form action="{{ route('admin.costumes.destroy', $costume->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this costume?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="ui-btn-danger ui-btn-sm">
                Delete Costume
            </button>
        </form>
    </div>
</x-app-layout>


