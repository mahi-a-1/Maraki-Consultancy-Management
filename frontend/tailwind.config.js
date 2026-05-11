/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{js,jsx}'],
  theme: {
    extend: {
      colors: {
        primary: {
          50:  '#f0f7ee',
          100: '#dceeda',
          200: '#b9ddb4',
          300: '#8ec487',
          400: '#60a657',
          500: '#4a8040',
          600: '#3d6b35',
          700: '#2f5228',
          800: '#243d1e',
          900: '#182a14',
          950: '#0c150a',
        },
        secondary: {
          50:  '#f7fce8',
          100: '#eef8d0',
          200: '#daf1a0',
          300: '#c8e870',
          400: '#b8dc4a',
          500: '#aecf5a',
          600: '#8fb040',
          700: '#6e8a2f',
          800: '#526620',
          900: '#374414',
          950: '#1c220a',
        },
      },
      fontFamily: {
        sans: ['Inter', 'sans-serif'],
      }
    },
  },
  plugins: [],
}
