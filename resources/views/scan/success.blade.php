<x-guest-layout>
	<div class="ui-alert ui-alert-good py-4 sm:px-5 sm:py-5">
		<p class="font-mono text-[11.5px] tracking-[0.1em] uppercase">Assignment status</p>
		<h1 class="mt-1 font-display text-[26px] font-normal leading-tight tracking-[-0.01em]">Successfully assigned</h1>
		<p class="mt-2 text-sm text-brand">
			@isset($item)
				<span class="font-semibold">{{ $item->code }}</span> ({{ $item->costume->name }}) is now linked to you.
			@else
				The costume item is now linked to you.
			@endisset
		</p>
	</div>

	<div class="mt-5">
		<a href="{{ route('dashboard') }}" class="ui-btn w-full justify-center w-full">
			Back to dashboard
		</a>
	</div>
</x-guest-layout>
