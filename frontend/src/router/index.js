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
    component:()=>import('@/views/auth/LoginForm.vue')
  },
  {
    path: '/admin/dashboard',
    name: 'DASHBOARD',
    component:()=>import('@/views/admin/Dashboard.vue')
  },
  ...adminRoutes,
  ...clientRoutes
]

const router = createRouter({
  history:createWebHistory(),
  routes,
})
export default router
