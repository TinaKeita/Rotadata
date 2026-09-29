{{-- viena koncerta rinda saraksta skatā (izmanto gan gaidāmajos, gan pagātnes sarakstos) --}}
<div class="flex flex-wrap items-center justify-between gap-4 rounded-[14px] border border-line bg-surface px-4 py-3">
    <div class="min-w-0">
        <p class="text-sm font-semibold text-ink">{{ $event->title }}</p>
        <p class="ui-help mt-0.5">
            {{ $event->starts_at->format('l, d.m.Y · H:i') }}
            @if($event->location) · {{ $event->location }} @endif
        </p>
        @if($event->costumes->isNotEmpty())
            <div class="mt-2 max-w-sm space-y-1.5">
                @foreach($event->costumeReadiness() as $row)
                    <div class="flex items-center gap-2 text-[11px]">
                        <span class="w-28 shrink-0 truncate font-medium text-ink-muted">{{ $row['costume']->name }}</span>
                        <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-surface-sunk">
                            <div class="h-full rounded-full bg-brand" style="width: {{ $row['percent'] }}%"></div>
                        </div>
                        <span class="shrink-0 text-ink-soft">{{ $row['assigned'] }}/{{ $row['target'] }}</span>
                        @if($row['shortfall'] > 0)
                            <span class="shrink-0 font-medium text-rust" title="Only {{ $row['total'] }} in inventory, {{ $row['shortfall'] }} short">⚠ {{ $row['shortfall'] }} short</span>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
    <div class="flex shrink-0 items-center gap-2">
        <a href="{{ route('admin.events.edit', $event) }}"
            class="ui-btn-ghost ui-btn-sm">
            Edit
        </a>
        <form method="POST" action="{{ route('admin.events.destroy', $event) }}" onsubmit="return confirm('Delete “{{ $event->title }}”? This cannot be undone.');">
            @csrf
            @method('DELETE')
            <button type="submit" class="ui-btn-danger ui-btn-sm">
                Delete
            </button>
        </form>
    </div>
</div>
