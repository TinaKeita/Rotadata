@props(['number', 'title', 'delay' => 0])

{{-- viena priekšrocību kartīte: numurs čipā, virsraksts, apraksts (slotā) --}}
<div data-reveal="" data-reveal-delay="{{ $delay }}" class="min-w-0">
    <div class="mb-4 flex h-[34px] w-[34px] items-center justify-center rounded-lg border border-[#DCE4DA] bg-brand-tint">
        <span class="font-mono text-xs font-medium text-brand">{{ $number }}</span>
    </div>
    <h3 class="mb-[7px] font-body text-[16.5px] font-semibold tracking-[-0.01em]">{{ $title }}</h3>
    <p class="text-pretty text-[15px] text-ink-muted">{{ $slot }}</p>
</div>
