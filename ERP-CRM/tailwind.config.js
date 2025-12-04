/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
  ],
  theme: {
    extend: {
      colors: {
        'primary': '#3498db',
        'primary-dark': '#2980b9',
        'secondary': '#2c3e50',
        'sidebar': '#34495e',
        'danger': '#e74c3c',
        'success': '#27ae60',
        'warning': '#f39c12',
      }
    },
  },
  plugins: [],
}
