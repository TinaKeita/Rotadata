@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full rounded-md border-l-2 border-brand-primary bg-brand-light/50 ps-3 pe-4 py-2 text-start text-base font-medium text-brand-accent transition duration-150 ease-in-out dark:border-brand-secondary dark:bg-darkbrand-light/50 dark:text-brand-light'
            : 'block w-full rounded-md border-l-2 border-transparent ps-3 pe-4 py-2 text-start text-base font-medium text-gray-600 transition duration-150 ease-in-out hover:bg-brand-light/35 hover:text-brand-accent dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
