{{-- galvenā darbība – zaļa "pill" poga (skat. .ui-btn app.css) --}}
<button {{ $attributes->merge(['type' => 'submit', 'class' => 'ui-btn']) }}>
    {{ $slot }}
</button>
