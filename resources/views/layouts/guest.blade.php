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

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased dark:text-gray-100">
        <div class="guest-bg relative">
            <div class="toggle-switch fixed right-4 top-4 z-10 sm:right-6 sm:top-6">
                <label class="switch-label">
                    <input type="checkbox" class="checkbox" x-model="darkMode" aria-label="Toggle dark mode">
                    <span class="slider"></span>
                </label>
            </div>

            <div class="guest-card">
                <div class="mb-5 flex justify-center">
                    <a href="/" class="inline-flex items-center justify-center rounded-full border border-brand-primary/20 bg-brand-light/40 p-3 dark:border-brand-secondary/35 dark:bg-darkbrand-light/40">
                        <x-application-logo class="w-10 h-10 fill-current text-brand-accent dark:text-brand-light" />
                    </a>
                </div>

                {{ $slot }}
            </div>
        </div>
    </body>
</html>
