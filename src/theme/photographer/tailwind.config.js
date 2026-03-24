/** @type {import('tailwindcss').Config} */
const path = require('path');

module.exports = {
  content: [
    path.join(__dirname, '**/*.php'),
    path.join(__dirname, '**/*.js'),
    path.join(__dirname, '../../lib/*.php'),
    path.join(__dirname, '../../lib/**/*.php'),
  ],
  theme: {
    extend: {
      fontFamily: {
        sans: ['Inter', 'Helvetica Neue', 'Arial', 'sans-serif'],
        serif: ['Playfair Display', 'Georgia', 'serif'],
      },
      colors: {
        'photo-black': '#1a1a1a',
        'photo-gray': '#f4f4f4',
        'photo-accent': '#555555',
      },
    },
  },
  plugins: [],
};
