<x-guest-layout>
	{{-- pieslēdzies, bet nav šīs grupas dalībnieks --}}
	<div class="rounded-xl border border-red-300/80 bg-red-50 px-4 py-4 sm:px-5 sm:py-5 text-red-800 dark:border-red-500/40 dark:bg-red-900/20 dark:text-red-300">
		<p class="text-xs font-semibold uppercase tracking-[0.16em]">Not allowed</p>
		<h1 class="mt-1 text-lg font-semibold">You're not in this group</h1>
		<p class="mt-2 text-sm text-red-700/90 dark:text-red-300/90">
			This costume item belongs to <span class="font-semibold">{{ $item->costume->group->name }}</span>.
			Only members of that group can claim it. Ask your teacher to add you to the group.
		</p>
	</div>

	<div class="mt-5 flex items-center gap-3">
		<a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center rounded-lg border border-brand-primary/25 bg-brand-primary px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-accent focus:outline-none focus:ring-2 focus:ring-brand-secondary/50">
			Back to dashboard
		</a>
		<form method="POST" action="{{ route('logout') }}">
			@csrf
			<button type="submit" class="text-sm font-medium text-gray-600 hover:text-brand-primary dark:text-gray-300 dark:hover:text-brand-secondary">
				Log out
			</button>
		</form>
	</div>
</x-guest-layout>
