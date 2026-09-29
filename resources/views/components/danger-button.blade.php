{{-- neatgriezeniska darbība (dzēšana) --}}
<button {{ $attributes->merge(['type' => 'submit', 'class' => 'ui-btn-danger-solid']) }}>
    {{ $slot }}
</button>
