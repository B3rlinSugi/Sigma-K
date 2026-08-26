import type { Config } from "tailwindcss";

const config: Config = {
  darkMode: ["class"],
  content: [
    "./src/pages/**/*.{js,ts,jsx,tsx,mdx}",
    "./src/components/**/*.{js,ts,jsx,tsx,mdx}",
    "./src/app/**/*.{js,ts,jsx,tsx,mdx}",
  ],
  theme: {
    extend: {
      colors: {
        background: "hsl(var(--background))",
        foreground: "hsl(var(--foreground))",
        card: {
          DEFAULT: "hsl(var(--card))",
          foreground: "hsl(var(--card-foreground))",
        },
        popover: {
          DEFAULT: "hsl(var(--popover))",
          foreground: "hsl(var(--popover-foreground))",
        },
        primary: {
          DEFAULT: "#0B2A4A", // KemenPANRB Navy Primary
          50: "#f0f5fa",
          100: "#e0ebf5",
          200: "#c2d7ec",
          300: "#94b9df",
          400: "#5e94cf",
          500: "#3974bc",
          600: "#27599c",
          700: "#1f477e",
          800: "#1c3d67",
          900: "#0B2A4A",
          950: "#071b31",
          foreground: "#ffffff",
        },
        gold: {
          DEFAULT: "#D4AF37", // Garuda Gold Accent
          50: "#fbf8ec",
          100: "#f5eecb",
          200: "#eddba0",
          300: "#e4c46c",
          400: "#dcaf42",
          500: "#D4AF37",
          600: "#b98c28",
          700: "#946823",
          800: "#795223",
          900: "#674421",
        },
        secondary: {
          DEFAULT: "#f1f5f9",
          foreground: "#0f172a",
        },
        muted: {
          DEFAULT: "#f8fafc",
          foreground: "#64748b",
        },
        accent: {
          DEFAULT: "#0284c7",
          foreground: "#ffffff",
        },
        destructive: {
          DEFAULT: "#ef4444",
          foreground: "#ffffff",
        },
        border: "#e2e8f0",
        input: "#e2e8f0",
        ring: "#0B2A4A",
      },
      borderRadius: {
        lg: "0.5rem",
        md: "0.375rem",
        sm: "0.25rem",
      },
      fontFamily: {
        sans: ["var(--font-inter)", "system-ui", "sans-serif"],
        heading: ["var(--font-outfit)", "system-ui", "sans-serif"],
      },
    },
  },
  plugins: [],
};
export default config;
