<x-guest-layout>
	{{-- QR kods nav atpazīts – vecā vai bojātā birka --}}
	<div class="rounded-xl border border-amber-300/80 bg-amber-50 px-4 py-4 sm:px-5 sm:py-5 text-amber-800 dark:border-amber-500/40 dark:bg-amber-900/20 dark:text-amber-300">
		<p class="text-xs font-semibold uppercase tracking-[0.16em]">Unknown code</p>
		<h1 class="mt-1 text-lg font-semibold">This QR code isn't recognised</h1>
		<p class="mt-2 text-sm text-amber-700/90 dark:text-amber-300/90">
			The label may have been replaced with a new one, or it belongs to a different system.
			Check the item's printed code (for example <span class="font-semibold">BRU-01</span>) with your teacher and scan the current label.
		</p>
	</div>

	<div class="mt-5">
		<a href="/" class="inline-flex w-full items-center justify-center rounded-lg border border-brand-primary/25 bg-brand-primary px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-accent focus:outline-none focus:ring-2 focus:ring-brand-secondary/50">
			Home
		</a>
	</div>
</x-guest-layout>
