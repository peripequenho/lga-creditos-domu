import type { Config } from 'tailwindcss';

const config: Config = {
  content: [
    './app/**/*.{ts,tsx}',
    './components/**/*.{ts,tsx}',
  ],
  theme: {
    extend: {
      colors: {
        lga: {
          primary: '#0F766E',
          primaryHover: '#115E59',
          dark: '#0B1220',
        },
        domu: {
          ink: '#1B2233',
          accent: '#F59E0B',
        },
      },
    },
  },
  plugins: [],
};

export default config;
