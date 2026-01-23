import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes: RouteRecordRaw[] = [
  {
    path: '/login',
    name: 'login',
    component: () => import('@/views/LoginView.vue'),
    meta: { requiresAuth: false }
  },
  {
    path: '/',
    component: () => import('@/components/layout/AppLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        name: 'dashboard',
        component: () => import('@/views/DashboardView.vue')
      },
      {
        path: 'orders',
        name: 'orders',
        component: () => import('@/views/OrdersView.vue')
      },
      {
        path: 'orders/:id',
        name: 'order-details',
        component: () => import('@/views/OrderDetailsView.vue'),
        props: true
      },
      {
        path: 'products',
        name: 'products',
        component: () => import('@/views/ProductsView.vue')
      },
      {
        path: 'extras',
        name: 'extras',
        component: () => import('@/views/ExtrasView.vue')
      },
      {
        path: 'stock',
        name: 'stock',
        component: () => import('@/views/StockView.vue')
      },
      {
        path: 'loyalty',
        name: 'loyalty',
        component: () => import('@/views/LoyaltyView.vue')
      },
      {
        path: 'users',
        name: 'users',
        component: () => import('@/views/UsersView.vue')
      },
      {
        path: 'users/:id',
        name: 'user-details',
        component: () => import('@/views/UserDetailsView.vue'),
        props: true
      }
    ]
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: '/'
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

router.beforeEach(async (to, _from, next) => {
  const authStore = useAuthStore()
  const requiresAuth = to.meta.requiresAuth !== false

  if (requiresAuth) {
    const isAuthenticated = await authStore.checkAuth()

    if (!isAuthenticated) {
      next({ name: 'login', query: { redirect: to.fullPath } })
      return
    }
  } else if (to.name === 'login' && authStore.isAuthenticated) {
    next({ name: 'dashboard' })
    return
  }

  next()
})

export default router
