<x-guest-layout>
	{{-- tērpa vienība jau ir piešķirta – parāda piešķires informāciju --}}
	<div class="rounded-xl border border-amber-300/80 bg-amber-50 px-4 py-4 sm:px-5 sm:py-5 text-amber-800 dark:border-amber-500/40 dark:bg-amber-900/20 dark:text-amber-300">
		<p class="text-xs font-semibold uppercase tracking-[0.16em]">Assignment status</p>
		<h1 class="mt-1 text-lg font-semibold">Assigned</h1>
		<p class="mt-2 text-sm text-amber-700/90 dark:text-amber-300/90">This costume item is linked to a member.</p>
	</div>

	<dl class="mt-5 divide-y divide-gray-200 rounded-lg border border-gray-200 text-sm dark:divide-gray-700 dark:border-gray-700">
		<div class="flex justify-between gap-4 px-4 py-3">
			<dt class="text-gray-500 dark:text-gray-400">Costume</dt>
			<dd class="text-right font-medium text-gray-800 dark:text-gray-100">{{ $item->costume->name }}</dd>
		</div>
		@if($item->costume->group)
			<div class="flex justify-between gap-4 px-4 py-3">
				<dt class="text-gray-500 dark:text-gray-400">Group</dt>
				<dd class="text-right font-medium text-gray-800 dark:text-gray-100">{{ $item->costume->group->name }}</dd>
			</div>
		@endif
		<div class="flex justify-between gap-4 px-4 py-3">
			<dt class="text-gray-500 dark:text-gray-400">Assigned to</dt>
			<dd class="text-right font-medium text-gray-800 dark:text-gray-100">{{ $item->user?->name ?? 'Unknown' }}</dd>
		</div>
		@if($item->assigned_at)
			<div class="flex justify-between gap-4 px-4 py-3">
				<dt class="text-gray-500 dark:text-gray-400">Assigned on</dt>
				<dd class="text-right font-medium text-gray-800 dark:text-gray-100">{{ $item->assigned_at->format('d.m.Y H:i') }}</dd>
			</div>
		@endif
	</dl>

	<div class="mt-5">
		<a href="{{ route('dashboard') }}" class="inline-flex w-full items-center justify-center rounded-lg border border-brand-primary/25 bg-brand-primary px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-accent focus:outline-none focus:ring-2 focus:ring-brand-secondary/50">
			Back to dashboard
		</a>
	</div>
</x-guest-layout>
