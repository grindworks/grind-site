/** @type {import('tailwindcss').Config} */
const path = require('path');

module.exports = {
  content: [
    path.join(__dirname, '**/*.php'),
    path.join(__dirname, '**/*.js'),
    path.join(__dirname, '../../lib/**/*.php'),
    path.join(__dirname, '../../admin/config/editor_blocks.php'),
  ],
  theme: {
    extend: {
      colors: {
        grinds: {
          red: '#4f46e5',
          dark: '#18181b', // Zinc-900
          gray: '#fafafa', // Zinc-50
        },
      },
      fontFamily: {
        sans: ['Inter', 'system-ui', 'sans-serif'],
        mono: ['JetBrains Mono', 'ui-monospace', 'monospace'],
      },
    },
  },
  plugins: [require('@tailwindcss/typography')],
};
