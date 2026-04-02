/** @type {import('tailwindcss').Config} */
const path = require('path');

module.exports = {
  content: [
    path.join(__dirname, '**/*.php'),
    path.join(__dirname, '**/*.js'),
    path.join(__dirname, '../../lib/*.php'),
    path.join(__dirname, '../../lib/**/*.php'),
    path.join(__dirname, '../../admin/config/editor_blocks.php'),
  ],
  safelist: [
    // Safelist for dynamic grid classes used in functions.php
    {
      pattern: /grid-cols-\d+/,
      variants: ['md'],
    },
    'md:grid-cols-[1fr_2fr]',
    'md:grid-cols-[2fr_1fr]',
  ],
  theme: {
    extend: {
      colors: {
        corp: {
          main: '#0f172a',
          accent: '#2563eb',
          light: '#f8fafc',
          text: '#334155',
          border: '#e2e8f0',
        },
      },
      fontFamily: {
        sans: ['Inter', 'Noto Sans JP', 'Helvetica Neue', 'Arial', 'sans-serif'],
      },
    },
  },
  plugins: [],
};
