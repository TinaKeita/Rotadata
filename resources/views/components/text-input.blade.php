@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'w-full rounded-lg border-gray-300 bg-white text-gray-800 shadow-sm focus:border-brand-primary focus:ring-brand-secondary/50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:focus:border-brand-secondary dark:focus:ring-brand-secondary/40']) }}>
