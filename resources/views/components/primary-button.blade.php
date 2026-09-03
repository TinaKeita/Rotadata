<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center rounded-lg border border-brand-primary/20 bg-brand-primary px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white shadow-sm transition duration-150 hover:bg-brand-accent focus:outline-none focus:ring-2 focus:ring-brand-secondary/60 focus:ring-offset-2 dark:border-darkbrand-primary dark:bg-darkbrand-secondary dark:hover:bg-darkbrand-accent dark:focus:ring-brand-secondary/50 dark:focus:ring-offset-gray-800']) }}>
    {{ $slot }}
</button>
