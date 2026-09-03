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
                brand: {
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
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
                logo: ['Playfair Display', 'serif'],
            },
        }
    },

    plugins: [forms],
};
