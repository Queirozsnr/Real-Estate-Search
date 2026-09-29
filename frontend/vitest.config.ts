import { fileURLToPath } from 'node:url'
import { defineConfig } from 'vitest/config'
import { defineVitestProject } from '@nuxt/test-utils/config'

export default defineConfig({
  test: {
    projects: [
      // Pure TypeScript (utils): plain Node, fast.
      {
        resolve: {
          alias: { '~': fileURLToPath(new URL('./', import.meta.url)) },
        },
        test: {
          name: 'unit',
          include: ['tests/unit/**/*.spec.ts'],
          environment: 'node',
        },
      },
      // Components: full Nuxt runtime (auto-imports, Nuxt UI).
      await defineVitestProject({
        test: {
          name: 'nuxt',
          include: ['tests/components/**/*.spec.ts'],
          environment: 'nuxt',
          hookTimeout: 60_000,
        },
      }),
    ],
  },
})
