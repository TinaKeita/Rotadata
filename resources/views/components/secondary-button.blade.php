<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center rounded-lg border border-brand-primary/30 bg-brand-light/40 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-brand-accent shadow-sm transition duration-150 hover:bg-brand-light/70 focus:outline-none focus:ring-2 focus:ring-brand-secondary/60 focus:ring-offset-2 disabled:opacity-25 dark:border-brand-secondary/35 dark:bg-darkbrand-light/40 dark:text-brand-light dark:hover:bg-darkbrand-light/60 dark:focus:ring-brand-secondary/50 dark:focus:ring-offset-gray-800']) }}>
    {{ $slot }}
</button>
