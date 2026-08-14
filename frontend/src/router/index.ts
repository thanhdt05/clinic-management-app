import { createRouter, createWebHistory } from 'vue-router'
import AppLayout from '@/layout/AppLayout.vue'
import { useAuthStore } from '@/stores/auth'

import HomeView from '../views/HomeView.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/login',
      name: 'login',
      component: () => import('@/views/auth/Login.vue'),
  
      meta: {
        guestOnly: true,
      },
    },
    {
      path: '/access',
      name: 'access',
      component: () => import('@/views/auth/Access.vue'),
    },

    {
      path: '/',
      component: AppLayout,

      meta: {
        requiresAuth: true,
      },

      children: [
        {
          path: '',
          name: 'dashboard',
          component: () => import('@/views/Dashboard.vue'),
        },
      ],
    },

    
  ],
})

router.beforeEach(async (to) => {
  const auth = useAuthStore()

  if (!auth.initialized)
    await auth.initialize()

  if (to.meta.requiresAuth && !auth.isAuthenticated){
    return {
      name: 'login',
      query: {
        redirect: to.fullPath,
      },
    }
  }

  if (to.meta.guestOnly && auth.isAuthenticated){
    return {
      name: 'dashboard',
    }
  }

  const permission = to.meta.permission

  if (typeof permission === 'string' && !auth.can(permission)){
    return {
      name: 'access',
    }
  }  
})

export default router
