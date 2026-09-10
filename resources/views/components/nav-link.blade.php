@props(['active' => false, 'badge' => null])

@php
    // pamata izskats + aktīvās/neaktīvās saites stāvoklis sānjoslā
    $base = 'group flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition duration-150';
    $state = ($active ?? false)
        ? ' bg-white/15 text-white'
        : ' text-white/65 hover:bg-white/10 hover:text-white';
@endphp

<a {{ $attributes->merge(['class' => $base.$state]) }}>
    @isset($icon)
        <span class="shrink-0 text-white/75 group-hover:text-white {{ ($active ?? false) ? 'text-white' : '' }}">{{ $icon }}</span>
    @endisset

    <span class="flex-1 truncate">{{ $slot }}</span>

    @if(! is_null($badge))
        <span class="shrink-0 rounded-full bg-white/15 px-1.5 py-0.5 text-[11px] font-semibold tabular-nums text-white/85">{{ $badge }}</span>
    @endif
</a>
