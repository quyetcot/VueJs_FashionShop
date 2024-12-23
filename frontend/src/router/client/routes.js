import routeNames from './routeNames'
import routePaths from './routePaths'

const clientRoutes = [
  {
    path: routePaths.HOMEPAGE,
    name: routeNames.HOMEPAGE,
    component: () => import('@/views/client/homepage/Homepage.vue')
  }
]

export default clientRoutes
