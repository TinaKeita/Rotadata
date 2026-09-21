@props(['name', 'labels', 'marks', 'highlight' => false])

{{-- salīdzinājuma kartīte: vienas un tās pašas rindas visām kartītēm, ✓ vai — katrā --}}
<div @class([
    'min-w-0 rounded-card border p-[22px]',
    'border-[#C9D6C6] bg-[#F4F7F3]' => $highlight,
    'border-line bg-surface' => ! $highlight,
])>
    <h3 class="mb-[18px] font-body text-base font-semibold">{{ $name }}</h3>

    @foreach ($labels as $i => $label)
        <div @class([
            'flex items-baseline gap-2.5 border-t py-2 text-[14.5px]',
            'border-[#DEE6DB]' => $highlight,
            'border-[#F0EEE9]' => ! $highlight,
        ])>
            @if ($marks[$i])
                <span class="shrink-0 text-[13px] text-brand" aria-hidden="true">✓</span>
                <span class="sr-only">Included:</span>
                <span class="min-w-0 text-pretty text-ink">{{ $label }}</span>
            @else
                <span class="shrink-0 text-[13px] text-ink-soft" aria-hidden="true">—</span>
                <span class="sr-only">Not included:</span>
                <span class="min-w-0 text-pretty text-ink-soft">{{ $label }}</span>
            @endif
        </div>
    @endforeach
</div>
