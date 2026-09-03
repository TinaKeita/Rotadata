@props(['active'])

@php
$classes = ($active ?? false)
            ? 'flex w-full items-center gap-2 rounded-md border-l-2 border-brand-secondary bg-black/20 px-3 py-2.5 text-sm font-medium text-white transition duration-150'
            : 'flex w-full items-center gap-2 rounded-md border-l-2 border-transparent px-3 py-2.5 text-sm font-medium text-white/70 hover:bg-black/15 hover:text-white transition duration-150';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
