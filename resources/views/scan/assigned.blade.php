<x-guest-layout>
	{{-- tērpa vienība jau ir piešķirta – parāda piešķires informāciju --}}
	<div class="ui-alert ui-alert-warn py-4 sm:px-5 sm:py-5">
		<p class="font-mono text-[11.5px] tracking-[0.1em] uppercase">Assignment status</p>
		<h1 class="mt-1 font-display text-[26px] font-normal leading-tight tracking-[-0.01em]">Assigned</h1>
		<p class="mt-2 text-sm text-rust">This costume item is linked to a member.</p>
	</div>

	@if($item->costume->image)
		<div class="mt-5 flex justify-center">
			<img src="{{ $item->costume->imageUrl() }}" alt="" class="h-24 w-24 rounded-lg border border-line object-cover">
		</div>
	@endif

	<dl class="mt-5 divide-y divide-line rounded-lg border border-line text-sm">
		<div class="flex justify-between gap-4 px-4 py-3">
			<dt class="text-ink-soft">Item code</dt>
			<dd class="text-right font-semibold tracking-wide text-ink">{{ $item->code }}</dd>
		</div>
		<div class="flex justify-between gap-4 px-4 py-3">
			<dt class="text-ink-soft">Costume</dt>
			<dd class="text-right font-medium text-ink">{{ $item->costume->name }}</dd>
		</div>
		@if($item->costume->group)
			<div class="flex justify-between gap-4 px-4 py-3">
				<dt class="text-ink-soft">Group</dt>
				<dd class="text-right font-medium text-ink">{{ $item->costume->group->name }}</dd>
			</div>
		@endif
		<div class="flex justify-between gap-4 px-4 py-3">
			<dt class="text-ink-soft">Assigned to</dt>
			<dd class="text-right font-medium text-ink">{{ $item->user?->name ?? 'Unknown' }}</dd>
		</div>
		@if($item->assigned_at)
			<div class="flex justify-between gap-4 px-4 py-3">
				<dt class="text-ink-soft">Assigned on</dt>
				<dd class="text-right font-medium text-ink">{{ $item->assigned_at->format('d.m.Y H:i') }}</dd>
			</div>
		@endif
	</dl>

	@if($isHolder)
		{{-- skenētājs pats tur šo vienību --}}
		<p class="ui-alert ui-alert-good mt-5">
			This item is already assigned to you.
		</p>
		<div class="mt-5">
			<a href="{{ route('dashboard') }}" class="ui-btn w-full justify-center w-full">
				Back to dashboard
			</a>
		</div>
	@elseif($canTakeOver)
		{{-- pieslēdzies grupas dalībnieks var pārņemt vienību sev --}}
		<div class="ui-alert ui-alert-good mt-5 py-4">
			<p class="text-sm text-ink-muted">
				Is this item now with you? Taking it over moves it to your inventory and clears it from
				<span class="font-semibold">{{ $item->user?->name ?? 'the current holder' }}</span>.
			</p>
			<div class="mt-4 flex items-center gap-3">
				<form method="POST" action="{{ route('scan.takeover', $item->qr_code) }}"
					onsubmit="return confirm('Take {{ $item->code }} over from {{ $item->user?->name }}?');">
					@csrf
					<x-primary-button class="justify-center">Take it over</x-primary-button>
				</form>
				<a href="{{ route('dashboard') }}" class="text-sm font-medium text-ink-muted hover:text-brand">
					Cancel
				</a>
			</div>
		</div>
	@elseif(! auth()->check())
		{{-- viesis: pieslēdzas, lai pārņemtu vienību sev --}}
		<div class="mt-5 rounded-[14px] border border-line bg-surface-sunk px-4 py-4">
			<p class="text-sm text-ink-muted">Is this item now with you? Sign in to take it over.</p>

			<form method="POST" action="{{ route('scan.authenticate', $item->qr_code) }}" class="mt-4 space-y-4"
				x-data="{ lock: {{ (int) session('scanLockSeconds', 0) }} }"
				x-init="if (lock > 0) { const t = setInterval(() => { if (--lock <= 0) clearInterval(t) }, 1000) }">
				@csrf

				<div>
					<x-input-label for="email" :value="__('Email')" />
					<x-text-input id="email" class="mt-1.5" type="email" name="email" :value="old('email')" required autocomplete="username" x-bind:disabled="lock > 0" />
					<x-input-error :messages="$errors->get('email')" class="mt-2" />
				</div>

				<div>
					<x-input-label for="password" :value="__('Password')" />
					<x-password-input id="password" class="ui-input mt-1.5" name="password" required autocomplete="current-password" x-bind:disabled="lock > 0" />
					<x-input-error :messages="$errors->get('password')" class="mt-2" />
				</div>

				@if(session('scanLockSeconds'))
					<p x-show="lock > 0" class="text-sm font-medium text-rust">
						Too many attempts. Try again in <span x-text="lock"></span>s.
					</p>
				@endif

				<div class="flex items-center gap-3 pt-1">
					<x-primary-button class="justify-center" x-bind:disabled="lock > 0" x-bind:class="lock > 0 ? 'opacity-50 cursor-not-allowed' : ''">
						{{ __('Continue') }}
					</x-primary-button>
					<a href="{{ route('dashboard') }}" class="text-sm font-medium text-ink-muted hover:text-brand">
						{{ __('Cancel') }}
					</a>
				</div>
			</form>
		</div>
	@else
		{{-- pieslēdzies, bet nav šīs grupas dalībnieks --}}
		<div class="mt-5">
			<a href="{{ route('dashboard') }}" class="ui-btn w-full justify-center w-full">
				Back to dashboard
			</a>
		</div>
	@endif
</x-guest-layout>
