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
