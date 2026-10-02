export default defineNuxtConfig({
  compatibilityDate: '2026-09-01',

  modules: ['@nuxt/ui'],

  css: ['~/assets/css/main.css'],

  // Components are grouped in folders (common/, property/, search/) but referenced without a prefix.
  components: [{ path: '~/components', pathPrefix: false }],

  devtools: { enabled: true },

  runtimeConfig: {
    // Server-only: where the Nuxt server proxy forwards /api/** (override with NUXT_API_BASE_URL).
    apiBaseUrl: 'http://localhost:8000',
  },

  app: {
    head: {
      htmlAttrs: { lang: 'en' },
      titleTemplate: '%s · Real Estate Search',
      meta: [{ name: 'description', content: 'Search and explore properties for sale in German cities.' }],
    },
  },

  // Light theme by default; the header toggle still switches to dark (and remembers the choice).
  colorMode: {
    preference: 'light',
    fallback: 'light',
  },

  // Icons are bundled at build time so the UI does not depend on the Iconify API at runtime.
  icon: {
    serverBundle: 'local',
    clientBundle: { scan: true },
  },

  // Keep the build self-contained (no font provider requests during the Docker build).
  fonts: {
    provider: 'none',
  },

  typescript: {
    strict: true,
  },
})
