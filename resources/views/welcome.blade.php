<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="csrf-token" content="{{ csrf_token() }}">
	<title>{{ config('app.name', 'Rotadata') }}</title>
	@vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="landing-bg">
	<main class="landing-main">
		<section class="landing-card">
			<p class="text-xs sm:text-sm font-semibold uppercase tracking-[0.2em] text-brand-primary dark:text-brand-secondary">Rotadata</p>
			<h1 class="mt-3 text-2xl font-semibold leading-tight text-brand-accent dark:text-brand-light sm:text-4xl">Costume Inventory, Simplified</h1>
			<p class="mt-4 max-w-2xl text-sm sm:text-base text-gray-600 dark:text-gray-300">
				Manage groups, assign costume items, and track inventory with a clean workflow for teachers and students.
			</p>

			<div class="mt-7 flex flex-col sm:flex-row sm:flex-wrap items-stretch sm:items-center gap-3">
				@auth
					<a href="{{ route('dashboard') }}" class="inline-flex justify-center items-center rounded-lg border border-brand-primary/20 bg-brand-primary px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-accent">
						Go to Dashboard
					</a>
				@else
					<a href="{{ route('login') }}" class="inline-flex justify-center items-center rounded-lg border border-brand-primary/20 bg-brand-primary px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-accent">
						Log In
					</a>
				@endauth
			</div>
		</section>
	</main>
</body>
</html>