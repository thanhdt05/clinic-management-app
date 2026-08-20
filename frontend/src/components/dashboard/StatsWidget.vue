<script setup lang="ts">
import type { StatsOverview } from '@/types/stats'

const props = defineProps<{
  data?: StatsOverview
  loading?: boolean
}>()

function formatCurrency(value?: number) {
  if (value === undefined || value === null) return '0 ₫'
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(value)
}
</script>

<template>
  <!-- 1. Total Patients -->
  <div class="col-span-12 lg:col-span-6 xl:col-span-3">
    <div class="card mb-0 h-full flex flex-col justify-between">
      <div class="flex justify-between items-start mb-4">
        <div>
          <span class="block text-muted-color font-medium mb-2">Total Patients</span>
          <div class="text-surface-900 dark:text-surface-0 font-bold text-2xl">
            <span v-if="loading" class="animate-pulse text-muted-color">...</span>
            <span v-else>{{ data?.total_patients?.toLocaleString('vi-VN') ?? 0 }}</span>
          </div>
        </div>
        <div class="flex items-center justify-center bg-blue-100 dark:bg-blue-400/10 rounded-border" style="width: 2.75rem; height: 2.75rem">
          <i class="pi pi-users text-blue-500 text-xl"></i>
        </div>
      </div>
      <div class="text-sm">
        <span class="text-primary font-medium">Registered </span>
        <span class="text-muted-color">active patients</span>
      </div>
    </div>
  </div>

  <!-- 2. Today's Appointments -->
  <div class="col-span-12 lg:col-span-6 xl:col-span-3">
    <div class="card mb-0 h-full flex flex-col justify-between">
      <div class="flex justify-between items-start mb-4">
        <div>
          <span class="block text-muted-color font-medium mb-2">Today's Appointments</span>
          <div class="text-surface-900 dark:text-surface-0 font-bold text-2xl">
            <span v-if="loading" class="animate-pulse text-muted-color">...</span>
            <span v-else>{{ data?.today_appointments?.toLocaleString('vi-VN') ?? 0 }}</span>
          </div>
        </div>
        <div class="flex items-center justify-center bg-cyan-100 dark:bg-cyan-400/10 rounded-border" style="width: 2.75rem; height: 2.75rem">
          <i class="pi pi-calendar text-cyan-500 text-xl"></i>
        </div>
      </div>
      <div class="text-sm">
        <span class="text-cyan-500 font-medium">Scheduled </span>
        <span class="text-muted-color">for today</span>
      </div>
    </div>
  </div>

  <!-- 3. Monthly Revenue -->
  <div class="col-span-12 lg:col-span-6 xl:col-span-3">
    <div class="card mb-0 h-full flex flex-col justify-between">
      <div class="flex justify-between items-start mb-4">
        <div>
          <span class="block text-muted-color font-medium mb-2">Monthly Revenue</span>
          <div class="text-surface-900 dark:text-surface-0 font-bold text-2xl text-green-600 dark:text-green-400">
            <span v-if="loading" class="animate-pulse text-muted-color">...</span>
            <span v-else>{{ formatCurrency(data?.monthly_revenue) }}</span>
          </div>
        </div>
        <div class="flex items-center justify-center bg-green-100 dark:bg-green-400/10 rounded-border" style="width: 2.75rem; height: 2.75rem">
          <i class="pi pi-dollar text-green-500 text-xl"></i>
        </div>
      </div>
      <div class="text-sm">
        <span class="text-green-500 font-medium">Paid invoices </span>
        <span class="text-muted-color">this month</span>
      </div>
    </div>
  </div>

  <!-- 4. Low Stock Medicines -->
  <div class="col-span-12 lg:col-span-6 xl:col-span-3">
    <div class="card mb-0 h-full flex flex-col justify-between">
      <div class="flex justify-between items-start mb-4">
        <div>
          <span class="block text-muted-color font-medium mb-2">Low Stock Medicines</span>
          <div class="text-surface-900 dark:text-surface-0 font-bold text-2xl" :class="(data?.low_stock_medicines ?? 0) > 0 ? 'text-orange-500' : ''">
            <span v-if="loading" class="animate-pulse text-muted-color">...</span>
            <span v-else>{{ data?.low_stock_medicines?.toLocaleString('vi-VN') ?? 0 }}</span>
          </div>
        </div>
        <div class="flex items-center justify-center bg-orange-100 dark:bg-orange-400/10 rounded-border" style="width: 2.75rem; height: 2.75rem">
          <i class="pi pi-exclamation-triangle text-orange-500 text-xl"></i>
        </div>
      </div>
      <div class="text-sm">
        <span class="text-orange-500 font-medium">Alert: </span>
        <span class="text-muted-color">Stock &le; 10 units</span>
      </div>
    </div>
  </div>
</template>
