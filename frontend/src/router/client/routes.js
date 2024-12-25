import routeNames from './routeNames'
import routePaths from './routePaths'

const clientRoutes = [
  {
      path:"/",
      component:()=>import('@/views/layouts/ClientLayout.vue'),
      children:[
        {
          path: routePaths.HOMEPAGE,
          name: routeNames.HOMEPAGE,
          component: () => import('@/views/client/homepage/Homepage.vue')
        }
      ]
    }
  
]

export default clientRoutes
