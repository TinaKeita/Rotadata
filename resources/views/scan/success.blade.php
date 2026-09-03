<x-guest-layout>
	<div class="rounded-xl border border-emerald-300/80 bg-emerald-50 px-4 py-4 sm:px-5 sm:py-5 text-emerald-800 dark:border-emerald-500/40 dark:bg-emerald-900/20 dark:text-emerald-300">
		<p class="text-xs font-semibold uppercase tracking-[0.16em]">Assignment status</p>
		<h1 class="mt-1 text-lg font-semibold">Successfully assigned</h1>
		<p class="mt-2 text-sm text-emerald-700/90 dark:text-emerald-300/90">The costume item is now linked to the selected member.</p>
	</div>

	<div class="mt-5">
		<a href="{{ route('dashboard') }}" class="inline-flex w-full items-center justify-center rounded-lg border border-brand-primary/25 bg-brand-primary px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-accent focus:outline-none focus:ring-2 focus:ring-brand-secondary/50">
			Back to dashboard
		</a>
	</div>
</x-guest-layout>
