/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{vue,js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        primary: {
          50: '#fdf4f3',
          100: '#fce7e4',
          200: '#fbd3ce',
          300: '#f7b4ab',
          400: '#f08879',
          500: '#e5604e',
          600: '#d24432',
          700: '#b03726',
          800: '#913123',
          900: '#792e24',
          950: '#41140e',
        },
        coffee: {
          50: '#faf6f1',
          100: '#f3ebe0',
          200: '#e6d4be',
          300: '#d6b896',
          400: '#c59a6e',
          500: '#b98253',
          600: '#ab6e47',
          700: '#8f573d',
          800: '#744737',
          900: '#5f3b2e',
          950: '#321d17',
        }
      }
    },
  },
  plugins: [],
}
