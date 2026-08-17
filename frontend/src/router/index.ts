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

        {
          path: 'patients',
          name: 'patients',
          component: () => import('@/views/patients/Patients.vue'),

          props: {
            title: 'Patients',
          },

          meta: {
            permission: 'PATIENTS.FINDALL',
          },
        },

        {
          path: 'appointments',
          name: 'appointments',
          component: () => import('@/views/ModulePlaceholder.vue'),

          props: {
            title: 'Appointments',
          },

          meta: {
            permission: 'APPOINTMENTS.FINDALL',
          },
        },

        {
          path: 'examinations',
          name: 'examinations',
          component: () => import('@/views/ModulePlaceholder.vue'),

          props: {
            title: 'Examinations',
          },

          meta: {
            permission: 'EXAMINATIONS.FINDALL',
          },
        },

        {
          path: 'prescriptions',
          name: 'prescriptions',
          component: () => import('@/views/ModulePlaceholder.vue'),

          props: {
            title: 'Prescriptions',
          },

          meta: {
            permission: 'PRESCRIPTIONS.FINDALL',
          },
        },

        {
          path: 'specialties',
          name: 'specialties',
          component: () => import('@/views/specialties/Specialties.vue'),

          props: {
            title: 'Specialties',
          },

          meta: {
            permission: 'SPECIALTIES.FINDALL',
          },
        },

        {
          path: 'doctors',
          name: 'doctors',
          component: () => import('@/views/doctors/Doctors.vue'),

          props: {
            title: 'Doctors',
          },

          meta: {
            permission: 'DOCTORS.FINDALL',
          },
        },

        {
          path: 'medicines',
          name: 'medicines',
          component: () => import('@/views/ModulePlaceholder.vue'),

          props: {
            title: 'Medicines',
          },

          meta: {
            permission: 'MEDICINES.FINDALL',
          },
        },

        {
          path: 'invoices',
          name: 'invoices',
          component: () => import('@/views/ModulePlaceholder.vue'),

          props: {
            title: 'Invoices',
          },

          meta: {
            permission: 'INVOICES.FINDALL',
          },
        },

        {
          path: 'users',
          name: 'users',
          component: () => import('@/views/users/Users.vue'),

          props: {
            title: 'Users',
          },

          meta: {
            permission: 'USERS.FINDALL',
          },
        },
      ],
    },
  ],
})

router.beforeEach(async (to) => {
  const auth = useAuthStore()

  if (!auth.initialized) await auth.initialize()

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return {
      name: 'login',
      query: {
        redirect: to.fullPath,
      },
    }
  }

  if (to.meta.guestOnly && auth.isAuthenticated) {
    return {
      name: 'dashboard',
    }
  }

  const permission = to.meta.permission

  if (typeof permission === 'string' && !auth.can(permission)) {
    return {
      name: 'access',
    }
  }
})

export default router
