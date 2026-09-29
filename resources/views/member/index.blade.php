<x-app-layout>
    {{-- studenta inventārs vienā grupā: kas šobrīd rokās (ar atdošanu) un pagātnes tērpi --}}
    <x-slot name="header">
        <x-page-header :eyebrow="$group->name.' · '.($setName ? 'Set: '.$setName : 'No set')" title="My inventory"
            subtitle="The costume items you have right now. Return an item here once you've handed it back." />
    </x-slot>

    <div class="mb-6">
        <a href="{{ route('dashboard') }}" class="ui-back">&larr; Back to dashboard</a>
    </div>

    <section data-reveal="" class="ui-card !p-0">
        <div class="flex items-center gap-3 border-b border-line px-5 py-4 sm:px-6">
            <h2 class="ui-heading">With you now</h2>
            <span class="ui-chip {{ $items->isNotEmpty() ? 'ui-chip-good' : '' }}">{{ $items->count() }}</span>
        </div>

        @forelse($items as $item)
            <div class="flex flex-wrap items-center gap-x-4 gap-y-2 border-b border-line-soft px-5 py-4 last:border-b-0 sm:px-6">
                <div class="flex min-w-0 flex-1 items-center gap-4">
                    @if($item->costume->image)
                        <img src="{{ $item->costume->imageUrl() }}" alt="" class="h-11 w-11 shrink-0 rounded-lg border border-line object-cover">
                    @endif
                    <div class="min-w-0">
                        <p class="truncate text-[15.5px] font-semibold">{{ $item->costume->name }}</p>
                        <p class="font-mono text-[12px] text-ink-soft">
                            {{ $item->code ?? '#NR.'.$item->id }}@if($item->assigned_at) · since {{ $item->assigned_at->format('d.m.Y') }}@endif
                        </p>
                    </div>
                </div>

                <form method="POST" action="{{ route('members.costumes.unassign', $item) }}" class="shrink-0">
                    @csrf
                    <button type="submit" class="ui-btn-ghost ui-btn-sm">Return</button>
                </form>
            </div>
        @empty
            <p class="px-5 py-8 text-center text-[15px] text-ink-muted sm:px-6">No costumes with you right now.</p>
        @endforelse
    </section>

    @if($history->isNotEmpty())
        <section data-reveal="" data-reveal-delay="80" class="mt-6">
            <h3 class="ui-eyebrow mb-3">Past costumes</h3>
            <div class="ui-card !py-1">
                @foreach($history as $log)
                    <div class="ui-row flex-wrap text-[14.5px]">
                        <span class="min-w-0">
                            <span class="font-medium">{{ $log->item?->costume?->name ?? 'costume removed' }}</span>
                            <span class="font-mono text-[12px] text-ink-soft">· {{ $log->item?->code ?? '—' }}</span>
                        </span>
                        <span class="font-mono text-[12px] text-ink-soft">
                            {{ $log->assigned_at->format('d.m.Y') }} &rarr; {{ $log->returned_at->format('d.m.Y') }}
                            {{-- tas pats iemesls, ko redz skolotājs tērpa vēsturē --}}
                            @if($log->return_note === 'transfer')
                                (handed over to {{ $log->returnedBy?->name ?? 'another member' }})
                            @elseif($log->return_note === 'admin')
                                (taken back by teacher)
                            @elseif($log->return_note === 'left_group')
                                (left the group)
                            @endif
                        </span>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</x-app-layout>
