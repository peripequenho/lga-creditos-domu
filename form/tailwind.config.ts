import type { Config } from 'tailwindcss';

/**
 * LGA Command Design System — Tailwind config for the public credit form.
 * Tokens mirror lga-design-system/src/app/globals.css (single source of truth).
 * Keep token *values* in sync; the form keeps legacy names (lga-primary,
 * domu-accent, etc.) remapped to LGA Command palette so existing className
 * references continue to work.
 */
const config: Config = {
  darkMode: 'class',
  content: [
    './app/**/*.{ts,tsx}',
    './components/**/*.{ts,tsx}',
  ],
  theme: {
    extend: {
      fontFamily: {
        sans: ['var(--font-geist-sans)', 'system-ui', 'sans-serif'],
        mono: ['var(--font-geist-mono)', 'ui-monospace', 'monospace'],
      },
      colors: {
        // ===== LGA Command semantic tokens =====
        'bg-base':        '#080A0C',
        'bg-secondary':   '#0E1114',
        'surface':        '#15191E',
        'surface-raised': '#1B2026',
        'border-color':   '#2A3038',
        'fg-primary':     '#F4F1EA',
        'fg-secondary':   '#B8B2A7',
        'fg-muted':       '#7E8790',
        'accent-bone':    '#E8E3D8',
        'accent-steel':   '#5E7184',
        'accent-olive':   '#5F6B58',
        'state-ok':       '#4F8A5B',
        'state-warn':     '#C1843A',
        'state-risk':     '#A6403A',
        'state-crit':     '#7E2525',
        'state-info':     '#5E7184',

        // ===== Legacy names remapped to LGA Command =====
        // bone is the institutional accent (was teal)
        lga: {
          primary:      '#E8E3D8', // bone (was #0F766E teal)
          primaryHover: '#F4F1EA', // fg-primary
          dark:         '#080A0C', // bg-base
        },
        domu: {
          ink:    '#15191E', // surface (was #1B2233)
          accent: '#5E7184', // steel (was #F59E0B amber)
        },
      },
      borderRadius: {
        DEFAULT: '4px',
        sm: '2px',
        md: '3px',
        lg: '4px',
        xl: '4px',
        '2xl': '4px',
      },
    },
  },
  plugins: [],
};

export default config;
