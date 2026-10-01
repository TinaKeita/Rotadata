<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }"
    x-init="$watch('darkMode', val => {
        localStorage.setItem('darkMode', val);
        document.documentElement.classList.toggle('dark', val);
    })"
    :class="{ 'dark': darkMode }"
>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Rotadata') }}</title>
        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">

        {{-- tie paši Bunny Fonts fonti kā landing lapā --}}
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=newsreader:400|public-sans:400,500,600|ibm-plex-mono:400,500&display=swap" rel="stylesheet">

        {{-- landing-reveal.js: elementi ar data-reveal parādās ar vieglu kustību (slēptais stāvoklis tikai no JS) --}}
        @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/landing-reveal.js'])
    </head>

    <body class="min-h-screen overflow-x-clip bg-paper font-body text-base leading-[1.55] text-ink antialiased">
        {{-- tumšā režīma pārslēgs stūrī, tāpat kā landing un login lapās --}}
        <div class="toggle-switch fixed right-4 top-[22px] z-50 sm:right-6 sm:top-[26px]">
            <label class="switch-label">
                <input type="checkbox" class="checkbox" x-model="darkMode" aria-label="Toggle dark mode">
                <span class="slider"></span>
            </label>
        </div>

        {{-- peldošā navigācijas kapsula (tāpat kā landing lapā) --}}
        @include('layouts.navigation')

        <main class="mx-auto w-full max-w-[1100px] px-4 pb-16 pt-4 sm:px-6 sm:pt-8">
            @isset($header)
                <div class="mb-8">
                    {{ $header }}
                </div>
            @endisset

            <x-flash />

            {{ $slot }}
        </main>

        <footer class="mx-auto flex max-w-[1100px] flex-wrap items-center gap-4 border-t border-line px-4 py-8 text-[13.5px] text-ink-soft sm:px-6">
            <span class="flex items-center gap-2">
                <img src="{{ asset('favicon.svg') }}" alt="" width="18" height="18" class="block h-[18px] w-[18px]">
                © {{ date('Y') }} Rotadata
            </span>
            <a href="mailto:info@rotadata.lv" class="ml-auto hover:text-ink">info@rotadata.lv</a>
        </footer>
    </body>
</html>
