import { createRouter, createWebHistory } from 'vue-router'

import clientRoutes from './client/routes'
import adminRoutes from './admin/routes'

const routes = [
  {
    path:'/',
    name:'default',
    redirect:'/login'
  },
  {
    path: '/login',
    name: 'LOGIN',
    component:()=>import('@/views/auth/loginForm.vue')
  },
  ...adminRoutes,
  ...clientRoutes
]

const router = createRouter({
  history:createWebHistory(),
  routes,
})
export default router
