import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            colors: {
                // jaunie dizaina žetoni landing lapas pārbūvei 
                paper: '#FBFAF8',
                surface: { DEFAULT: '#FFFFFF', sunk: '#F5F3EF' },
                ink: { DEFAULT: '#1B1B19', muted: '#4A4A45', soft: '#6E6A61' }, // soft – gaišākā krāsa, kas drīkst nest tekstu
                line: { DEFAULT: '#E7E4DE', strong: '#D9D5CD', soft: '#F4F2EE' },
                sand: '#D1A980', // tikai akcents, nekad teksts uz balta fona
                rust: { DEFAULT: '#8A4B33', tint: '#F6E9E4' },

                brand: {
                    DEFAULT: '#2F5D46',
                    dark: '#24301F',
                    tint: '#EDF1EC',
                    // vecie nosaukumi paliek, lai pārējā lietotne nesalūst
                    primary: '#748873',
                    secondary: '#D1A980',
                    accent: '#4f6150',
                    light: '#E5E0D8',
                },

                darkbrand: {
                    primary: '#3d4d3c',
                    secondary: '#a07850',
                    accent: '#2d3d2d',
                    light: '#2a2724',
                }
            },

            fontFamily: {
                // sans apzināti nemainām, kamēr Public Sans nav ielādēts (2. solis)
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
                logo: ['Playfair Display', 'serif'],
                // Public Sans landing lapas pamatteksts; vēlāk (pārējās lapas) to var piesaistīt pie sans
                body: ['"Public Sans"', 'Helvetica', 'Arial', 'sans-serif'],
                display: ['Newsreader', 'Georgia', 'serif'],
                mono: ['"IBM Plex Mono"', 'ui-monospace', 'monospace'],
            },

            borderRadius: {
                card: '12px',
                block: '16px',
            },

            // vienīgās ēnas dizainā: hero kartīte un navigācijas kapsula
            boxShadow: {
                hero: '0 1px 2px rgba(27,27,25,.04), 0 30px 60px -40px rgba(27,27,25,.4)',
                nav: '0 1px 2px rgba(27,27,25,.03), 0 10px 30px -22px rgba(27,27,25,.35)',
            },

            maxWidth: {
                shell: '1180px',
            },

            transitionTimingFunction: {
                reveal: 'cubic-bezier(.22,.61,.36,1)',
            },
        }
    },

    plugins: [forms],
};
