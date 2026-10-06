@props(['backHref' => url('/'), 'backLabel' => 'Home'])

{{-- login/register izkārtojums landing lapas stilā: tie paši žetoni, fonti, navigācijas kapsula un kartīte;
     pārējās guest lapas (scan, paroles) joprojām lieto x-guest-layout --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Rotadata') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">

    {{-- tie paši Bunny Fonts fonti kā landing lapā --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=newsreader:400|public-sans:400,500,600|ibm-plex-mono:400,500&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col overflow-x-clip bg-paper font-body text-base leading-[1.55] text-ink antialiased">

    {{-- vienkāršota landing navigācijas kapsula: logotips un atpakaļ saite --}}
    <header class="flex justify-center px-4 py-3.5">
        <nav aria-label="Main"
            class="flex max-w-full items-center gap-2 rounded-full border border-line bg-paper/80 py-[7px] pl-4 pr-[7px] shadow-nav backdrop-blur-[14px]">
            <a href="{{ url('/') }}" class="flex shrink-0 items-center gap-2 text-[15px] font-semibold tracking-[-0.01em] hover:text-brand">
                <img src="{{ asset('favicon.svg') }}" alt="" width="22" height="22" class="block h-[22px] w-[22px]">
                Rotadata
            </a>
            <span class="mx-1 h-[18px] w-px shrink-0 bg-line"></span>
            <a href="{{ $backHref }}" class="whitespace-nowrap rounded-full px-3 py-1.5 text-sm font-medium text-ink-muted hover:bg-surface-sunk hover:text-ink">
                &larr; {{ $backLabel }}
            </a>
        </nav>
    </header>

    <main class="flex flex-1 flex-col items-center justify-center px-4 py-10 sm:px-6">
        <div class="w-full max-w-md rounded-block border border-line bg-surface px-6 py-8 shadow-hero sm:px-8 sm:py-10">
            <x-flash />

            {{ $slot }}
        </div>
    </main>

    <footer class="px-6 pb-8 pt-4 text-center text-[13.5px] text-ink-soft">
        © {{ date('Y') }} Rotadata · <a href="mailto:info@rotadata.lv" class="hover:text-ink">info@rotadata.lv</a>
    </footer>
</body>
</html>
