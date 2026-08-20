<script setup lang="ts">
import { computed } from 'vue'

import AppMenuItem from './AppMenuItem.vue'
import { useAuthStore } from '@/stores/auth'

interface MenuItem {
  label: string
  icon?: string
  to?: string
  permission?: string
  items?: MenuItem[]
}

interface MenuGroup {
  label: string
  items: MenuItem[]
}

const auth = useAuthStore()

const menu = computed<MenuGroup[]>(() => {
  const model: MenuGroup[] = [
    {
      label: 'Overview',
      items: [
        {
          label: 'Dashboard',
          icon: 'pi pi-fw pi-home',
          to: '/',
          permission: 'STATS.SHOW',
        },
      ],
    },

    {
      label: 'Clinic Operations',
      items: [
        {
          label: 'Patients',
          icon: 'pi pi-fw pi-users',
          to: '/patients',
          permission: 'PATIENTS.FINDALL',
        },
        {
          label: 'Appointments',
          icon: 'pi pi-fw pi-calendar',
          to: '/appointments',
          permission: 'APPOINTMENTS.FINDALL',
        },
        {
          label: 'Examinations',
          icon: 'pi pi-fw pi-file-edit',
          to: '/examinations',
          permission: 'EXAMINATIONS.FINDALL',
        },
        {
          label: 'Prescriptions',
          icon: 'pi pi-fw pi-file',
          to: '/prescriptions',
          permission: 'PRESCRIPTIONS.FINDALL',
        },
      ],
    },

    {
      label: 'Catalog & Inventory',
      items: [
        {
          label: 'Specialties',
          icon: 'pi pi-fw pi-sitemap',
          to: '/specialties',
          permission: 'SPECIALTIES.FINDALL',
        },
        {
          label: 'Doctors',
          icon: 'pi pi-fw pi-user',
          to: '/doctors',
          permission: 'DOCTORS.FINDALL',
        },
        {
          label: 'Medicines',
          icon: 'pi pi-fw pi-box',
          to: '/medicines',
          permission: 'MEDICINES.FINDALL',
        },
      ],
    },

    {
      label: 'Finance',
      items: [
        {
          label: 'Invoices',
          icon: 'pi pi-fw pi-receipt',
          to: '/invoices',
          permission: 'INVOICES.FINDALL',
        },
      ],
    },

    {
      label: 'System',
      items: [
        {
          label: 'Users',
          icon: 'pi pi-fw pi-users',
          to: '/users',
          permission: 'USERS.FINDALL',
        },
      ],
    },
  ]

  return model
    .map((group) => ({
      ...group,

      items: group.items.filter((item) => !item.permission || auth.can(item.permission)),
    }))
    .filter((group) => group.items.length > 0)
})
</script>

<template>
  <ul class="layout-menu">
    <template v-for="(item, index) in menu" :key="item.label">
      <AppMenuItem :item="item" :index="index" />
    </template>
  </ul>
</template>
