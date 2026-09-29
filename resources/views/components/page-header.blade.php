@props(['title', 'subtitle' => null, 'eyebrow' => null])

{{-- vienots lapas virsraksts (landing/paneļa stilā): mono uzraksts, serif virsraksts, apakšvirsraksts un darbības --}}
<div data-reveal="" class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div class="flex min-w-0 flex-col gap-2">
        @if($eyebrow)
            <p class="ui-eyebrow">{{ $eyebrow }}</p>
        @endif

        <h1 class="text-balance font-display text-[length:clamp(30px,3.6vw,40px)] font-normal leading-[1.08] tracking-[-0.02em] text-ink">
            {{ $title }}
        </h1>

        @if($subtitle)
            <p class="max-w-[60ch] text-pretty text-[15.5px] text-ink-muted">{{ $subtitle }}</p>
        @endif
    </div>

    @isset($actions)
        {{-- neobligāta darbību josla virsraksta labajā pusē --}}
        <div class="flex flex-wrap items-center gap-2 sm:shrink-0">
            {{ $actions }}
        </div>
    @endisset
</div>
