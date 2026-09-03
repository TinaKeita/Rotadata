<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center rounded-lg border border-red-300 bg-red-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white shadow-sm transition duration-150 hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-400 focus:ring-offset-2 dark:border-red-400/50 dark:bg-red-700 dark:hover:bg-red-600 dark:focus:ring-red-500 dark:focus:ring-offset-gray-800']) }}>
    {{ $slot }}
</button>
