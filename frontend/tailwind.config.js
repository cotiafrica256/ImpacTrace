/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{vue,js,ts}'],
  theme: {
    extend: {
      colors: {
        // Navy/gold — matches COTIA's existing branding
        navy: { 700: '#0b2545', 800: '#08203b', 900: '#061a30' },
        gold: { 400: '#e2b13c', 500: '#d4a12c' },
      },
    },
  },
  plugins: [],
}
