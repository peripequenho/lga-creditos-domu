import type { Config } from 'tailwindcss';

/**
 * Tailwind config para el form público de Domu.
 * Tokens espejo del theme Shopify Domu (Shrine PRO) · sincronizado con globals.css.
 *
 * Light-first · Poppins · verde #2E7D32 + rojo #DD1D1D
 * Nombres legacy (lga.*, domu.*) preservados y re-mapeados para no romper
 * componentes existentes que ya los referencian.
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
        sans: ['Poppins', 'system-ui', '-apple-system', 'sans-serif'],
        mono: ['ui-monospace', 'monospace'],
      },
      colors: {
        // ===== Domu surfaces (light) =====
        'bg-base':        '#FFFFFF',
        'bg-secondary':   '#F3F3F3',
        'surface':        '#FFFFFF',
        'surface-raised': '#F8F8F8',
        'border-color':   '#E5E5E5',
        'border-soft':    '#EEEEEE',
        'fg-primary':     '#1B1B1B',
        'fg-secondary':   '#2E2A39',
        'fg-muted':       '#6B6B6B',
        'fg-on-accent':   '#FFFFFF',

        // ===== Domu brand =====
        'accent-green':   '#2E7D32',
        'accent-green-hover': '#256528',
        'accent-red':     '#DD1D1D',
        'accent-red-hover': '#B81818',
        'accent-blue':    '#0F455B',
        'accent-bone':    '#F3F1EC',

        // ===== States =====
        'state-ok':       '#2E7D32',
        'state-ok-bg':    '#E8F5E9',
        'state-warn':     '#ED6C02',
        'state-warn-bg':  '#FFF4E5',
        'state-risk':     '#DD1D1D',
        'state-risk-bg':  '#FDEAEA',
        'state-info':     '#0F455B',
        'state-info-bg':  '#E5EEF2',

        // ===== Legacy remap (no romper código existente) =====
        // En la versión LGA-dark estos nombres apuntaban a tokens distintos;
        // ahora apuntan al verde principal de Domu para que los componentes
        // que ya usan `bg-lga-primary` etc. se vean correctos en el rebrand.
        lga: {
          primary:      '#2E7D32', // verde Domu (era bone #E8E3D8)
          primaryHover: '#256528', // verde hover (era #F4F1EA)
          dark:         '#1B1B1B', // ink (era #080A0C)
        },
        domu: {
          ink:    '#1B1B1B', // texto principal (era surface #15191E)
          accent: '#2E7D32', // verde primario (era steel)
        },
      },
      borderRadius: {
        DEFAULT: '6px',
        sm: '4px',
        md: '6px',
        lg: '8px',
        xl: '12px',
        '2xl': '16px',
      },
      boxShadow: {
        'domu-input':  '0 1px 0 rgba(27, 27, 27, 0.05)',
        'domu-button': '0 4px 5px rgba(27, 27, 27, 0.05)',
        'domu-card':   '0 1px 3px rgba(27, 27, 27, 0.08), 0 1px 2px rgba(27, 27, 27, 0.04)',
      },
    },
  },
  plugins: [],
};

export default config;
