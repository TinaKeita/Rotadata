<x-guest-layout>
	<div class="rounded-xl border border-amber-300/80 bg-amber-50 px-4 py-4 sm:px-5 sm:py-5 text-amber-800 dark:border-amber-500/40 dark:bg-amber-900/20 dark:text-amber-300">
		<p class="text-xs font-semibold uppercase tracking-[0.16em]">Assignment status</p>
		<h1 class="mt-1 text-lg font-semibold">Already assigned</h1>
		<p class="mt-2 text-sm text-amber-700/90 dark:text-amber-300/90">This costume item has already been linked to a member.</p>
	</div>

	<div class="mt-5">
		<a href="{{ route('dashboard') }}" class="inline-flex w-full items-center justify-center rounded-lg border border-brand-primary/25 bg-brand-primary px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-accent focus:outline-none focus:ring-2 focus:ring-brand-secondary/50">
			Back to dashboard
		</a>
	</div>
</x-guest-layout>
