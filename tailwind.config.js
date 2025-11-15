import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'selector',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/**/*.js',
        './app/Livewire/**/*.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            // Glassmorphism helpers and optional animation alias
            animation: {
                shake: 'shake 0.82s cubic-bezier(0.36, 0.07, 0.19, 0.97) both',
            },
            backdropBlur: {
                xs: '2px',
                xl: '24px',
                '2xl': '40px',
            },
        },
    },

    plugins: [forms],
};
