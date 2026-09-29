{{-- otršķirīga darbība – caurspīdīga "pill" poga ar kontūru --}}
<button {{ $attributes->merge(['type' => 'button', 'class' => 'ui-btn-ghost']) }}>
    {{ $slot }}
</button>
