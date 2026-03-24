/** @type {import('tailwindcss').Config} */

function withAlpha(variable) {
  return ({ opacityValue }) => {
    // Combine the custom CSS alpha variable with Tailwind's opacity variable using calc().
    // opacityValue is usually something like 'var(--tw-bg-opacity)' or a plain number.
    return `rgb(var(${variable}) / calc(var(${variable}-alpha, 1) * ${opacityValue || 1}))`;
  };
}

module.exports = {
  content: ['./src/admin/**/*.php', './src/lib/**/*.php', './src/assets/js/**/*.js'],
  safelist: [
    // Safelist for dynamic block classes (colors, grids)
    {
      pattern: /grid-cols-\d+/,
      variants: ['sm', 'md', 'lg'],
    },
    'md:grid-cols-[1fr_2fr]',
    'md:grid-cols-[2fr_1fr]',
    // Safelist for dynamic components (buttons, badges)
    'btn-primary',
    'btn-secondary',
    'btn-danger',
    'badge',
    'badge-success',
    'badge-warning',
    'badge-danger',
    'badge-draft',
  ],
  theme: {
    extend: {
      colors: {
        theme: {
          bg: withAlpha('--color-bg'),
          surface: withAlpha('--color-surface'),
          sidebar: withAlpha('--color-sidebar'),
          text: withAlpha('--color-text'),
          'text-muted': 'rgb(var(--color-text) / 0.6)',
          primary: withAlpha('--color-primary'),
          'on-primary': withAlpha('--color-on-primary'),
          secondary: withAlpha('--color-secondary'),
          'on-secondary': withAlpha('--color-on-secondary'),
          border: withAlpha('--color-border'),
          success: withAlpha('--color-success'),
          'on-success': withAlpha('--color-on-success'),
          danger: withAlpha('--color-danger'),
          'on-danger': withAlpha('--color-on-danger'),
          warning: withAlpha('--color-warning'),
          'on-warning': withAlpha('--color-on-warning'),
          info: withAlpha('--color-info'),
          'on-info': withAlpha('--color-on-info'),
        },
      },
      borderRadius: {
        theme: 'var(--border-radius)',
      },
      boxShadow: {
        theme: 'var(--box-shadow)',
      },
      zIndex: {
        60: '60',
        70: '70',
        80: '80',
        90: '90',
        100: '100',
      },
      fontFamily: {
        sans: ['var(--font-body)', 'ui-sans-serif', 'system-ui', 'sans-serif'],
        body: ['var(--font-body)', 'ui-sans-serif', 'system-ui', 'sans-serif'],
      },
    },
  },
  plugins: [],
};
