import routeNames from './routeNames'
import routePaths from './routePaths'
const adminRoutes = [
  {
    path: routePaths.DASHBOARD,
    name: routeNames.DASHBOARD,
    component: () => import('@/views/admin/Dashboard.vue')
  }
]

export default adminRoutes
