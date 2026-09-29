<x-guest-layout>
	{{-- QR kods nav atpazīts – vecā vai bojātā birka --}}
	<div class="ui-alert ui-alert-warn py-4 sm:px-5 sm:py-5">
		<p class="font-mono text-[11.5px] tracking-[0.1em] uppercase">Unknown code</p>
		<h1 class="mt-1 font-display text-[26px] font-normal leading-tight tracking-[-0.01em]">This QR code isn't recognised</h1>
		<p class="mt-2 text-sm text-rust">
			The label may have been replaced with a new one, or it belongs to a different system.
			Check the item's printed code (for example <span class="font-semibold">BRU-01</span>) with your teacher and scan the current label.
		</p>
	</div>

	<div class="mt-5">
		<a href="/" class="ui-btn w-full justify-center w-full">
			Home
		</a>
	</div>
</x-guest-layout>
