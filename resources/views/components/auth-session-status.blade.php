@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'ui-alert ui-alert-good font-medium']) }}>
        {{ $status }}
    </div>
@endif
