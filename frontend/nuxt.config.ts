// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  compatibilityDate: '2024-11-01',
  devtools: { enabled: false },

  modules: [
    '@nuxtjs/tailwindcss',
  ],

  css: [
    '~/assets/css/main.css',
  ],

  runtimeConfig: {
    // Private keys (available only on server-side)
    backendUrl: process.env.NUXT_BACKEND_URL || 'http://backend:80',
    useMock: process.env.NUXT_USE_MOCK === 'true',

    // Public keys (exposed to client)
    public: {
      appName: 'Beijer Ref Optimization Dashboard',
      defaultVariant: 'cost',
      useMock: process.env.NUXT_USE_MOCK === 'true',
    },
  },

  typescript: {
    strict: true,
  },

  app: {
    head: {
      title: 'Beijer Ref | Task Scheduling & Assignment Optimizer',
      meta: [
        { charset: 'utf-8' },
        { name: 'viewport', content: 'width=device-width, initial-scale=1' },
        { name: 'description', content: 'Optimal task scheduling and employee assignment analytical dashboard' },
      ],
      link: [
        { rel: 'icon', type: 'image/svg+xml', href: '/favicon.svg' },
      ],
    },
  },
})

