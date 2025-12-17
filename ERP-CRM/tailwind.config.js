import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
        'primary': '#3498db',
        'primary-dark': '#2980b9',
        'secondary': '#2c3e50',
        'sidebar': '#34495e',
        'danger': '#e74c3c',
        'success': '#27ae60',
        'warning': '#f39c12',
      },
        },
    },

    plugins: [forms],
};
