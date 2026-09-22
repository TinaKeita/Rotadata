{{-- viena koncerta rinda saraksta skatā (izmanto gan gaidāmajos, gan pagātnes sarakstos) --}}
<div class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-gray-200 bg-white px-4 py-3 shadow-sm dark:border-gray-700 dark:bg-gray-800">
    <div class="min-w-0">
        <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $event->title }}</p>
        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
            {{ $event->starts_at->format('l, d.m.Y · H:i') }}
            @if($event->location) · {{ $event->location }} @endif
        </p>
        @if($event->costumes->isNotEmpty())
            <div class="mt-2 max-w-sm space-y-1.5">
                @foreach($event->costumeReadiness() as $row)
                    <div class="flex items-center gap-2 text-[11px]">
                        <span class="w-28 shrink-0 truncate font-medium text-gray-600 dark:text-gray-300">{{ $row['costume']->name }}</span>
                        <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700">
                            <div class="h-full rounded-full bg-brand-primary dark:bg-brand-secondary" style="width: {{ $row['percent'] }}%"></div>
                        </div>
                        <span class="shrink-0 text-gray-500 dark:text-gray-400">{{ $row['assigned'] }}/{{ $row['target'] }}</span>
                        @if($row['shortfall'] > 0)
                            <span class="shrink-0 font-medium text-amber-600 dark:text-amber-400" title="Only {{ $row['total'] }} in inventory, {{ $row['shortfall'] }} short">⚠ {{ $row['shortfall'] }} short</span>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
    <div class="flex shrink-0 items-center gap-2">
        <a href="{{ route('admin.events.edit', $event) }}"
            class="inline-flex items-center rounded-lg border border-brand-primary/25 bg-brand-light/50 px-3 py-1.5 text-xs font-semibold text-brand-accent transition hover:bg-brand-light/75 dark:border-brand-secondary/35 dark:bg-darkbrand-light/45 dark:text-brand-light">
            Edit
        </a>
        <form method="POST" action="{{ route('admin.events.destroy', $event) }}" onsubmit="return confirm('Delete “{{ $event->title }}”? This cannot be undone.');">
            @csrf
            @method('DELETE')
            <button type="submit" class="inline-flex items-center rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100 dark:border-red-900 dark:bg-red-950/40 dark:text-red-300">
                Delete
            </button>
        </form>
    </div>
</div>
