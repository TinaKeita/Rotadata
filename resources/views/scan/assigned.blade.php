<x-guest-layout>
	{{-- tērpa vienība jau ir piešķirta – parāda piešķires informāciju --}}
	<div class="rounded-xl border border-amber-300/80 bg-amber-50 px-4 py-4 sm:px-5 sm:py-5 text-amber-800 dark:border-amber-500/40 dark:bg-amber-900/20 dark:text-amber-300">
		<p class="text-xs font-semibold uppercase tracking-[0.16em]">Assignment status</p>
		<h1 class="mt-1 text-lg font-semibold">Assigned</h1>
		<p class="mt-2 text-sm text-amber-700/90 dark:text-amber-300/90">This costume item is linked to a member.</p>
	</div>

	<dl class="mt-5 divide-y divide-gray-200 rounded-lg border border-gray-200 text-sm dark:divide-gray-700 dark:border-gray-700">
		<div class="flex justify-between gap-4 px-4 py-3">
			<dt class="text-gray-500 dark:text-gray-400">Item code</dt>
			<dd class="text-right font-semibold tracking-wide text-gray-800 dark:text-gray-100">{{ $item->code }}</dd>
		</div>
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

	@if($isHolder)
		{{-- skenētājs pats tur šo vienību --}}
		<p class="mt-5 rounded-lg border border-emerald-300/80 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-500/40 dark:bg-emerald-900/20 dark:text-emerald-300">
			This item is already assigned to you.
		</p>
		<div class="mt-5">
			<a href="{{ route('dashboard') }}" class="inline-flex w-full items-center justify-center rounded-lg border border-brand-primary/25 bg-brand-primary px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-accent focus:outline-none focus:ring-2 focus:ring-brand-secondary/50">
				Back to dashboard
			</a>
		</div>
	@elseif($canTakeOver)
		{{-- pieslēdzies grupas dalībnieks var pārņemt vienību sev --}}
		<div class="mt-5 rounded-xl border border-brand-primary/25 bg-brand-light/40 px-4 py-4 dark:border-brand-secondary/35 dark:bg-darkbrand-light/30">
			<p class="text-sm text-gray-700 dark:text-gray-200">
				Is this item now with you? Taking it over moves it to your inventory and clears it from
				<span class="font-semibold">{{ $item->user?->name ?? 'the current holder' }}</span>.
			</p>
			<div class="mt-4 flex items-center gap-3">
				<form method="POST" action="{{ route('scan.takeover', $item->qr_code) }}"
					onsubmit="return confirm('Take {{ $item->code }} over from {{ $item->user?->name }}?');">
					@csrf
					<x-primary-button class="justify-center">Take it over</x-primary-button>
				</form>
				<a href="{{ route('dashboard') }}" class="text-sm font-medium text-gray-600 hover:text-brand-primary dark:text-gray-300 dark:hover:text-brand-secondary">
					Cancel
				</a>
			</div>
		</div>
	@elseif(! auth()->check())
		{{-- viesis: pieslēdzas, lai pārņemtu vienību sev --}}
		<div class="mt-5 rounded-xl border border-gray-200 bg-gray-50 px-4 py-4 dark:border-gray-700 dark:bg-gray-900/40">
			<p class="text-sm text-gray-700 dark:text-gray-200">Is this item now with you? Sign in to take it over.</p>

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
					<x-text-input id="password" class="mt-1.5" type="password" name="password" required autocomplete="current-password" x-bind:disabled="lock > 0" />
					<x-input-error :messages="$errors->get('password')" class="mt-2" />
				</div>

				@if(session('scanLockSeconds'))
					<p x-show="lock > 0" class="text-sm font-medium text-amber-600 dark:text-amber-400">
						Too many attempts. Try again in <span x-text="lock"></span>s.
					</p>
				@endif

				<div class="flex items-center gap-3 pt-1">
					<x-primary-button class="justify-center" x-bind:disabled="lock > 0" x-bind:class="lock > 0 ? 'opacity-50 cursor-not-allowed' : ''">
						{{ __('Continue') }}
					</x-primary-button>
					<a href="{{ route('dashboard') }}" class="text-sm font-medium text-gray-600 hover:text-brand-primary dark:text-gray-300 dark:hover:text-brand-secondary">
						{{ __('Cancel') }}
					</a>
				</div>
			</form>
		</div>
	@else
		{{-- pieslēdzies, bet nav šīs grupas dalībnieks --}}
		<div class="mt-5">
			<a href="{{ route('dashboard') }}" class="inline-flex w-full items-center justify-center rounded-lg border border-brand-primary/25 bg-brand-primary px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-accent focus:outline-none focus:ring-2 focus:ring-brand-secondary/50">
				Back to dashboard
			</a>
		</div>
	@endif
</x-guest-layout>
