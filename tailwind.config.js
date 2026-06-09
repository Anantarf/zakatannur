const defaultTheme = require('tailwindcss/defaultTheme');

/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['"Plus Jakarta Sans"', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: {
                    50: 'rgb(var(--color-brand-50-rgb) / <alpha-value>)',
                    100: 'rgb(var(--color-brand-100-rgb) / <alpha-value>)',
                    200: 'rgb(var(--color-brand-200-rgb) / <alpha-value>)',
                    300: 'rgb(var(--color-brand-300-rgb) / <alpha-value>)',
                    400: 'rgb(var(--color-brand-400-rgb) / <alpha-value>)',
                    500: 'rgb(var(--color-brand-500-rgb) / <alpha-value>)',
                    600: 'rgb(var(--color-brand-600-rgb) / <alpha-value>)',
                    700: 'rgb(var(--color-brand-700-rgb) / <alpha-value>)',
                    800: 'rgb(var(--color-brand-800-rgb) / <alpha-value>)',
                    900: 'rgb(var(--color-brand-900-rgb) / <alpha-value>)',
                    950: 'rgb(var(--color-brand-950-rgb) / <alpha-value>)',
                },
                neutral: {
                    50: 'rgb(var(--color-neutral-50-rgb) / <alpha-value>)',
                    100: 'rgb(var(--color-neutral-100-rgb) / <alpha-value>)',
                    200: 'rgb(var(--color-neutral-200-rgb) / <alpha-value>)',
                    300: 'rgb(var(--color-neutral-300-rgb) / <alpha-value>)',
                    400: 'rgb(var(--color-neutral-400-rgb) / <alpha-value>)',
                    500: 'rgb(var(--color-neutral-500-rgb) / <alpha-value>)',
                    600: 'rgb(var(--color-neutral-600-rgb) / <alpha-value>)',
                    700: 'rgb(var(--color-neutral-700-rgb) / <alpha-value>)',
                    800: 'rgb(var(--color-neutral-800-rgb) / <alpha-value>)',
                    900: 'rgb(var(--color-neutral-900-rgb) / <alpha-value>)',
                    950: 'rgb(var(--color-neutral-950-rgb) / <alpha-value>)',
                },
            },
            borderRadius: {
                'card': 'var(--radius-card)',
                'button': 'var(--radius-button)',
            },
        },
    },

    plugins: [require('@tailwindcss/forms')],
};
