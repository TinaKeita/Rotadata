<!DOCTYPE html>
<html   lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    x-data="{ darkMode: localStorage.getItem('darkMode') === 'true', mobileMenu: false }"
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
        <link href="https://fonts.bunny.net/css?family=playfair-display:400,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>

    <body class="font-sans antialiased text-gray-800 dark:text-gray-100">
        <div class="min-h-screen bg-[radial-gradient(1200px_500px_at_10%_-10%,rgba(116,136,115,0.2),transparent),radial-gradient(900px_400px_at_95%_-5%,rgba(209,169,128,0.2),transparent)] dark:bg-gray-900">
            <div class="min-h-screen lg:grid lg:grid-cols-[14.5rem_1fr] lg:grid-rows-[auto_1fr]">

                <div class="hidden lg:flex lg:row-start-1 lg:col-start-1 items-center gap-3 px-5 bg-brand-primary dark:bg-darkbrand-primary border-b border-black/10 dark:border-white/10">
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-3">
                        <x-application-logo class="block h-7 w-auto text-white" />
                        <h2 class="text-[1.05rem] font-logo italic font-semibold text-white/95 tracking-wide">
                            Rotadata
                        </h2>
                    </a>
                </div>

                <header class="lg:row-start-1 lg:col-start-2 sticky top-0 z-40 bg-white/95 dark:bg-gray-800/95 backdrop-blur border-b border-gray-200/80 dark:border-gray-700/80 shadow-[0_1px_2px_rgba(0,0,0,0.04)]">
                    <div class="px-4 py-3 sm:px-6 sm:py-4 lg:px-8 flex items-center justify-between gap-3">
                        <div class="min-w-0 flex items-center gap-3">
                            <button type="button"
                                class="inline-flex lg:hidden h-10 w-10 items-center justify-center rounded-lg border border-brand-primary/20 bg-brand-light/60 text-brand-accent dark:border-brand-light/30 dark:bg-darkbrand-light/80 dark:text-brand-light"
                                @click.stop="mobileMenu = !mobileMenu"
                                aria-label="Toggle menu"
                                :aria-expanded="mobileMenu.toString()">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                                </svg>
                            </button>

                            <a href="{{ route('dashboard') }}" class="inline-flex lg:hidden items-center gap-2">
                                <x-application-logo class="block h-7 w-auto text-brand-accent dark:text-brand-light" />
                                <span class="font-logo text-lg italic font-semibold tracking-wide text-brand-accent dark:text-brand-light">Rotadata</span>
                            </a>

                            <div class="min-w-0 hidden sm:block lg:block">
                                @isset($header)
                                    {{ $header }}
                                @endisset
                            </div>
                        </div>

                        <div class="toggle-switch shrink-0">
                            <label class="switch-label">
                                <input
                                    type="checkbox"
                                    class="checkbox"
                                    x-model="darkMode"
                                >
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>

                    <div class="px-4 pb-3 sm:px-6 lg:hidden sm:hidden">
                        @isset($header)
                            {{ $header }}
                        @endisset
                    </div>
                </header>

                <button x-show="mobileMenu" x-transition.opacity type="button" class="fixed inset-0 z-30 bg-black/30 lg:hidden" @click="mobileMenu = false" aria-label="Close navigation"></button>

                <aside class="lg:row-start-2 lg:col-start-1 lg:translate-y-0 lg:opacity-100 lg:pointer-events-auto fixed lg:static inset-x-4 top-[4.5rem] z-40 rounded-2xl lg:rounded-none shadow-2xl lg:shadow-none border border-brand-primary/20 lg:border-0 bg-brand-primary dark:bg-darkbrand-primary text-white border-r border-black/10 dark:border-white/10 transition duration-200"
                    :class="mobileMenu ? 'translate-y-0 opacity-100 pointer-events-auto' : '-translate-y-2 opacity-0 pointer-events-none'">
                    @include('layouts.navigation')
                </aside>

                <main class="lg:row-start-2 lg:col-start-2 p-3 sm:p-5 lg:p-7">
                    <div class="rounded-xl sm:rounded-2xl border border-gray-200/70 bg-white shadow-[0_8px_24px_rgba(0,0,0,0.06)] p-4 sm:p-6 dark:border-gray-700/70 dark:bg-gray-800 dark:shadow-none">
                        {{ $slot }}
                    </div>
                </main>

            </div>
        </div>
    </body>


</html>
