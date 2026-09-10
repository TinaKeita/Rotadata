@props(['name'])

{{-- sānjoslas ikonas (Tabler stila kontūras, 20px) --}}
@php
    $paths = [
        'dashboard' => '<path d="M4 4h6v8H4z"/><path d="M4 16h6v4H4z"/><path d="M14 12h6v8h-6z"/><path d="M14 4h6v4h-6z"/>',
        'members'   => '<path d="M9 7a3 3 0 1 0 0 6 3 3 0 0 0 0-6"/><path d="M3 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/><path d="M21 21v-2a4 4 0 0 0-3-3.85"/>',
        'costumes'  => '<path d="M8 4 5 6l1.5 3L8 8.5V20h8V8.5L17.5 9 19 6l-3-2-2 2h-4z"/>',
        'inventory' => '<path d="M3 7l9-4 9 4-9 4-9-4z"/><path d="M3 7v10l9 4 9-4V7"/><path d="M12 11v10"/>',
        'profile'   => '<path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8"/><path d="M6 21v-1a6 6 0 0 1 12 0v1"/>',
        'logout'    => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/>',
    ];
@endphp

<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none"
    stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
    {!! $paths[$name] ?? '' !!}
</svg>
