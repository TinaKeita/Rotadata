@props(['number', 'label'])

{{-- numurētās sadaļas atzīme ("01 BENEFITS") ar hairline zem tās --}}
<div data-reveal="" {{ $attributes->merge(['class' => 'mb-10 flex items-baseline gap-3.5 border-b border-line pb-3.5']) }}>
    <span class="font-mono text-[11.5px] tracking-[0.1em] text-brand">{{ $number }}</span>
    <span class="font-mono text-[11.5px] uppercase tracking-[0.1em] text-ink-soft">{{ $label }}</span>
</div>
