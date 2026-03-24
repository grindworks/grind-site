/** @type {import('tailwindcss').Config} */
const path = require('path');

module.exports = {
  content: [path.join(__dirname, '**/*.php'), path.join(__dirname, '../../lib/**/*.php')],
  theme: {
    extend: {
      colors: {
        theme: {
          primary: '#1e3a8a',
          'on-primary': '#ffffff',
          accent: '#dc2626',
          'on-accent': '#ffffff',
          bg: '#f8fafc',
          surface: '#ffffff',
          text: '#334155',
          border: '#e2e8f0',
        },
      },
      fontFamily: {
        serif: ['"Noto Serif JP"', '"Yu Mincho"', 'YuMincho', '"Hiragino Mincho ProN"', 'serif'],
        sans: ['"Noto Sans JP"', '"Helvetica Neue"', 'Arial', 'sans-serif'],
      },
    },
  },
  plugins: [],
};
