@props(['active' => false, 'badge' => null])

@php
    // kapsulas navigācijas saite (landing lapas stilā) ar neobligātu skaitītāju
    $base = 'inline-flex shrink-0 items-center gap-1.5 whitespace-nowrap rounded-full px-3 py-1.5 text-sm font-medium transition-colors';
    $state = ($active ?? false)
        ? ' bg-surface-sunk text-ink'
        : ' text-ink-muted hover:bg-surface-sunk hover:text-ink';
@endphp

<a {{ $attributes->merge(['class' => $base.$state]) }} @if($active) aria-current="page" @endif>
    <span class="truncate">{{ $slot }}</span>

    @if(! is_null($badge))
        <span class="font-mono text-[11.5px] text-ink-soft">{{ $badge }}</span>
    @endif
</a>
