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
      variants: ['md'],
    },
    'md:grid-cols-[1fr_2fr]',
    'md:grid-cols-[2fr_1fr]',
  ],
  theme: {
    extend: {
      colors: {
        grinds: {
          red: '#ef4444',
          dark: '#0f172a',
          gray: '#f8fafc',
        },
        brand: {
          50: '#f0fdfa',
          100: '#ccfbf1',
          200: '#99f6e4',
          300: '#5eead4',
          400: '#2dd4bf',
          500: '#14b8a6', // Teal as a pop color
          600: '#0d9488',
          700: '#0f766e',
          800: '#115e59',
          900: '#134e4a',
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
        sans: ['Inter', 'Helvetica Neue', 'Arial', 'sans-serif'],
        heading: ['"Space Grotesk"', 'Outfit', 'Helvetica Neue', 'sans-serif'],
      },
      boxShadow: {
        sharp: '4px 4px 0px 0px rgba(15, 23, 42, 1)',
        'sharp-hover': '8px 8px 0px 0px rgba(15, 23, 42, 1)',
      },
    },
  },
  plugins: [],
};
