import routeNames from './routeNames'
import routePaths from './routePaths'
const adminRoutes = [
  {
    path:"/admin",
    component:()=>import('@/views/layouts/AdminLayout.vue'),
    children:[
      {
        path: routePaths.DASHBOARD,
        name: routeNames.DASHBOARD,
        component: () => import('@/views/admin/Dashboard.vue')
      },
      {
        path: routePaths.LIST_CATEGORIES,
        name: routeNames.LIST_CATEGORIES,
        component: () => import('@/views/admin/categories/ListCategories.vue')
      }
    ]
  }
]

export default adminRoutes
