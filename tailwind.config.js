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
                slate: {
                    50: 'rgb(var(--color-slate-50-rgb) / <alpha-value>)',
                    100: 'rgb(var(--color-slate-100-rgb) / <alpha-value>)',
                    200: 'rgb(var(--color-slate-200-rgb) / <alpha-value>)',
                    300: 'rgb(var(--color-slate-300-rgb) / <alpha-value>)',
                    400: 'rgb(var(--color-slate-400-rgb) / <alpha-value>)',
                    500: 'rgb(var(--color-slate-500-rgb) / <alpha-value>)',
                    600: 'rgb(var(--color-slate-600-rgb) / <alpha-value>)',
                    700: 'rgb(var(--color-slate-700-rgb) / <alpha-value>)',
                    800: 'rgb(var(--color-slate-800-rgb) / <alpha-value>)',
                    900: 'rgb(var(--color-slate-900-rgb) / <alpha-value>)',
                    950: 'rgb(var(--color-slate-950-rgb) / <alpha-value>)',
                },
            },
            boxShadow: {
                'premium': '0 20px 40px -15px rgba(0, 0, 0, 0.05)',
                'premium-hover': '0 25px 50px -12px rgba(0, 0, 0, 0.10)',
                'glow-brand': '0 0 20px rgba(20, 184, 166, 0.34)',
            },
            borderRadius: {
                'card': 'var(--radius-card)',
                'button': 'var(--radius-button)',
            },
        },
    },

    plugins: [require('@tailwindcss/forms')],
};
