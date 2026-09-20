// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  compatibilityDate: '2024-11-01',
  devtools: { enabled: false },
  routeRules: {
    '/admin/**': { ssr: false },
    '/admin': { ssr: false },
    '/company/**': { ssr: false },
    '/company': { ssr: false },
    '/dashboard': { ssr: false },
    '/calendar': { ssr: false },
    '/contacts/**': { ssr: false },
    '/contacts': { ssr: false },
    '/time': { ssr: false },
    '/settings': { ssr: false },
    '/projects/**': { ssr: false },
    '/folders/**': { ssr: false }
  },
  modules: ['@nuxtjs/tailwindcss', '@nuxtjs/i18n'],
  i18n: {
    locales: [
      { code: 'de', file: 'de.json', name: 'Deutsch' },
      { code: 'en', file: 'en.json', name: 'English' },
      { code: 'sk', file: 'sk.json', name: 'Slovenčina' }
    ],
    defaultLocale: 'de',
    strategy: 'no_prefix',
    lazy: true,
    langDir: 'locales'
  },
  app: {
    head: {
      title: 'Taskster - Professionelles Projekt- & Bauleitermanagement',
      meta: [
        { charset: 'utf-8' },
        { name: 'viewport', content: 'width=device-width, initial-scale=1' },
        { name: 'description', content: 'Taskster - Enterprise Projekt- und Bauleitermanagement Plattform' }
      ],
      link: [
        { rel: 'icon', type: 'image/x-icon', href: '/favicon.ico' }
      ]
    }
  },
  tailwindcss: {
    exposeConfig: true
  }
})
