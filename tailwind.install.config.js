/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ['./src/install.php'],
  theme: {
    extend: {
      colors: {
        primary: {
          50: '#edf5ff',
          100: '#d0e2ff',
          200: '#a6c8ff',
          300: '#78a9ff',
          400: '#4589ff',
          500: '#0f62fe', // The signature blue
          600: '#0053ee',
          700: '#0043ce',
          800: '#002d9c',
          900: '#001d6c',
        },
      },
      borderRadius: {
        theme: '12px',
      },
      animation: {
        blob: 'blob 7s infinite',
      },
      keyframes: {
        blob: {
          '0%': { transform: 'translate(0px, 0px) scale(1)' },
          '33%': { transform: 'translate(30px, -50px) scale(1.1)' },
          '66%': { transform: 'translate(-20px, 20px) scale(0.9)' },
          '100%': { transform: 'translate(0px, 0px) scale(1)' },
        },
      },
    },
  },
  plugins: [],
};
