import { createApp } from 'vue'
import { createPinia } from 'pinia'
import * as Sentry from '@sentry/vue'

import App from './App.vue'
import router from './router'
import './assets/app.css'

const app = createApp(App)

Sentry.init({
  app,
  dsn: import.meta.env.VITE_SENTRY_DSN || undefined,
  enabled: Boolean(import.meta.env.VITE_SENTRY_DSN),
  environment: import.meta.env.MODE,
  tracesSampleRate: 0.1,
})

app.use(createPinia())
app.use(router)

app.mount('#app')
