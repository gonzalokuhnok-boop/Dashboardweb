export default {
  content: [
    "./index.html",
    "./src/**/*.{js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      animation: {
        'shimmer': 'shimmer 2s linear infinite',
        'pulse-red': 'pulse-red 2s infinite',
      },
      keyframes: {
        shimmer: {
          '0%': { transform: 'translateX(-100%)' },
          '100%': { transform: 'translateX(100%)' },
        },
        'pulse-red': {
          '0%, 100%': { boxShadow: '0 0 0 0 rgba(225, 29, 72, 0.4)' },
          '50%': { boxShadow: '0 0 0 10px rgba(225, 29, 72, 0)' },
        }
      }
    },
  },
  plugins: [],
}
