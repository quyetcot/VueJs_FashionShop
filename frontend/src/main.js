import App from './App.vue'
import { createApp } from 'vue'
import router from './router'
import i18n from './locales/i18n'
import vuetify from './plugins/vuetify'
import store from './store'


const app = createApp(App)

app.use(router)
app.use(store)
app.use(i18n)
app.use(vuetify)
app.mount('#app')
