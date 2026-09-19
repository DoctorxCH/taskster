import type { Config } from 'tailwindcss'

export default <Partial<Config>>{
  darkMode: 'class',
  theme: {
    extend: {
      fontFamily: {
        sans: ['Inter', 'system-ui', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', 'sans-serif']
      },
      colors: {
        accent: {
          DEFAULT: '#0891B2',
          hover: '#0E7490',
          subtle: '#ECFEFF',
          border: '#A5F3FC'
        },
        brand: {
          50: '#f0fdf4',
          100: '#dcfce7',
          200: '#bbf7d0',
          300: '#86efac',
          400: '#4ade80',
          500: '#22c55e',
          600: '#16a34a',
          700: '#15803d',
          800: '#166534',
          900: '#14532d',
          950: '#052e16'
        },
        taskster: {
          bg: '#F8FAFC',
          surface: '#FFFFFF',
          sunken: '#F1F5F9',
          border: '#E2E8F0',
          'border-strong': '#CBD5E1',
          text: '#0F172A',
          body: '#334155',
          muted: '#64748B',
          subtle: '#94A3B8',
          accent: '#0891B2',
          'accent-hover': '#0E7490'
        }
      },
      borderRadius: {
        'sm': '6px',
        'md': '10px',
        'lg': '14px'
      },
      boxShadow: {
        'sm': '0 1px 2px rgba(15, 23, 42, 0.06)',
        'md': '0 4px 12px rgba(15, 23, 42, 0.10)'
      }
    }
  }
}

