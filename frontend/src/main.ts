import { createApp } from 'vue'
import { createPinia } from 'pinia'

import PrimeVue from 'primevue/config'
import Aura from '@primeuix/themes/aura'
import ToastService from 'primevue/toastservice'
import ConfirmationService from 'primevue/confirmationservice'

import App from './App.vue'
import router from './router'

import '@/assets/tailwind.css'
import '@/assets/styles.scss'

const app = createApp(App)

const pinia = createPinia()

app.use(pinia)

app.use(router)

app.use(PrimeVue, {
  theme: {
    preset: Aura,
    options: {
      darkModeSelector: '.app-dark',
    },
  },
})

app.use(ToastService)
app.use(ConfirmationService)

app.mount('#app')