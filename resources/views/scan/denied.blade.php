<x-guest-layout>
	{{-- pieslēdzies, bet nav šīs grupas dalībnieks --}}
	<div class="ui-alert ui-alert-error py-4 sm:px-5 sm:py-5">
		<p class="font-mono text-[11.5px] tracking-[0.1em] uppercase">Not allowed</p>
		<h1 class="mt-1 font-display text-[26px] font-normal leading-tight tracking-[-0.01em]">You're not in this group</h1>
		<p class="mt-2 text-sm text-danger/90">
			Item <span class="font-semibold">{{ $item->code }}</span> ({{ $item->costume->name }}) belongs to
			<span class="font-semibold">{{ $item->costume->group->name }}</span>.
			Only members of that group can claim it. Ask your teacher to add you to the group.
		</p>
	</div>

	<div class="mt-5 flex items-center gap-3">
		<a href="{{ route('dashboard') }}" class="ui-btn justify-center">
			Back to dashboard
		</a>
		<form method="POST" action="{{ route('logout') }}">
			@csrf
			<button type="submit" class="text-sm font-medium text-ink-muted hover:text-brand">
				Log out
			</button>
		</form>
	</div>
</x-guest-layout>
