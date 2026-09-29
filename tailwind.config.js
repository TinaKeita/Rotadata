import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

// ļauj krāsu definēt kā CSS mainīgo (piem. "251 250 248"), lai tā pati Tailwind klase
// (piem. bg-paper) automātiski pielāgotos tumšajam režīmam, kad main pārslēdz .dark uz <html>
function withOpacityValue(variable) {
    return ({ opacityValue }) => {
        if (opacityValue === undefined) {
            return `rgb(var(${variable}))`;
        }
        return `rgb(var(${variable}) / ${opacityValue})`;
    };
}

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
                // jaunie dizaina žetoni landing lapas pārbūvei – "page chrome" toņi (fons, virsma, teksts,
                // līnijas, akcents) ir CSS mainīgie, lai landing lapa dabūtu tumšo režīmu bez katras
                // klases pārrakstīšanas; tumšās vērtības skat. resources/css/app.css (:root un .dark)
                paper: withOpacityValue('--color-paper'),
                surface: { DEFAULT: withOpacityValue('--color-surface'), sunk: withOpacityValue('--color-surface-sunk') },
                ink: {
                    DEFAULT: withOpacityValue('--color-ink'),
                    muted: withOpacityValue('--color-ink-muted'),
                    soft: withOpacityValue('--color-ink-soft'), // soft – gaišākā krāsa, kas drīkst nest tekstu
                },
                line: {
                    DEFAULT: withOpacityValue('--color-line'),
                    strong: withOpacityValue('--color-line-strong'),
                    soft: withOpacityValue('--color-line-soft'),
                },
                sand: '#D1A980', // tikai akcents, nekad teksts uz balta fona
                // rust – "nokavēts"/brīdinājuma tonis; CSS mainīgais, lai tumšajā režīmā paliktu salasāms
                rust: { DEFAULT: withOpacityValue('--color-rust'), tint: withOpacityValue('--color-rust-tint') },

                brand: {
                    // DEFAULT arī ir CSS mainīgais – gaišajā režīmā tumši zaļš teksta akcents, tumšajā
                    // režīmā spilgtāks, lai paliktu salasāms uz tumšā fona
                    DEFAULT: withOpacityValue('--color-brand'),
                    tint: withOpacityValue('--color-brand-tint'),
                    // dark paliek fiksēts: to lieto tikai CTA blokā, kas ar savu tumšo fonu
                    // ir apzināti "pretējs" pārējai lapai neatkarīgi no gaišā/tumšā režīma
                    dark: '#24301F',
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
                // visa lietotne lieto landing lapas pamatfontu (Public Sans ielādē app, guest un auth izkārtojumi)
                sans: ['"Public Sans"', ...defaultTheme.fontFamily.sans],
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
