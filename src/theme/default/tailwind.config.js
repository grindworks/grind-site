/** @type {import('tailwindcss').Config} */
const path = require('path');

module.exports = {
  content: [
    path.join(__dirname, '**/*.php'),
    path.join(__dirname, '**/*.js'),
    path.join(__dirname, '../../lib/**/*.php'),
  ],
  safelist: [
    // Safelist for dynamic block classes (colors, grids)
    {
      pattern: /grid-cols-\d+/,
      variants: ['sm', 'md', 'lg'],
    },
    'md:grid-cols-[1fr_2fr]',
    'md:grid-cols-[2fr_1fr]',
  ],
  theme: {
    extend: {
      colors: {
        grinds: {
          red: '#e60000',
          dark: '#1e293b',
          gray: '#f8fafc',
        },
        theme: {
          primary: 'rgb(var(--color-primary) / calc(var(--color-primary-alpha, 1) * <alpha-value>))',
          'on-primary': 'rgb(var(--color-on-primary) / calc(var(--color-on-primary-alpha, 1) * <alpha-value>))',
          bg: 'rgb(var(--color-bg) / calc(var(--color-bg-alpha, 1) * <alpha-value>))',
          surface: 'rgb(var(--color-surface) / calc(var(--color-surface-alpha, 1) * <alpha-value>))',
          text: 'rgb(var(--color-text) / calc(var(--color-text-alpha, 1) * <alpha-value>))',
          border: 'rgb(var(--color-border) / calc(var(--color-border-alpha, 1) * <alpha-value>))',
          success: 'rgb(var(--color-success) / calc(var(--color-success-alpha, 1) * <alpha-value>))',
          danger: 'rgb(var(--color-danger) / calc(var(--color-danger-alpha, 1) * <alpha-value>))',
          warning: 'rgb(var(--color-warning) / calc(var(--color-warning-alpha, 1) * <alpha-value>))',
        },
      },
      fontFamily: {
        sans: ['Helvetica Neue', 'Arial', 'Hiragino Kaku Gothic ProN', 'Hiragino Sans', 'Meiryo', 'sans-serif'],
      },
    },
  },
  plugins: [],
};
