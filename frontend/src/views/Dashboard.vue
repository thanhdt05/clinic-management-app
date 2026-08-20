<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useToast } from 'primevue/usetoast'
import BestSellingWidget from '@/components/dashboard/BestSellingWidget.vue'
import NotificationsWidget from '@/components/dashboard/NotificationsWidget.vue'
import RecentSalesWidget from '@/components/dashboard/RecentSalesWidget.vue'
import RevenueStreamWidget from '@/components/dashboard/RevenueStreamWidget.vue'
import StatsWidget from '@/components/dashboard/StatsWidget.vue'
import { statsService } from '@/services/statsService'
import type { DashboardStats } from '@/types/stats'

const toast = useToast()
const stats = ref<DashboardStats | null>(null)
const loading = ref(true)
const lastUpdated = ref<string>('')

async function fetchStats() {
  loading.value = true
  try {
    const response = await statsService.getDashboardStats()
    stats.value = response.data
    lastUpdated.value = new Date().toLocaleTimeString('vi-VN', {
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit',
    })
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load dashboard stats.',
      life: 4000,
    })
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchStats()
})
</script>

<template>
  <div class="flex flex-col gap-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
      <div class="flex items-center gap-3">
        <span v-if="lastUpdated" class="text-xs text-muted-color hidden md:inline">
          Updated: {{ lastUpdated }}
        </span>
        <Button
          icon="pi pi-refresh"
          label="Refresh"
          size="small"
          outlined
          :loading="loading"
          @click="fetchStats"
        />
      </div>
    </div>

    <div class="grid grid-cols-12 gap-8">
      <StatsWidget :data="stats?.overview" :loading="loading" />

      <div class="col-span-12 xl:col-span-6 flex flex-col gap-8">
        <RecentSalesWidget :recent-invoices="stats?.recent_invoices" :loading="loading" />
        <BestSellingWidget
          :top-medicines="stats?.top_prescribed_medicines"
          :top-doctors="stats?.top_doctors"
          :loading="loading"
        />
      </div>

      <div class="col-span-12 xl:col-span-6 flex flex-col gap-8">
        <RevenueStreamWidget :revenue-stream="stats?.revenue_stream" :loading="loading" />
        <NotificationsWidget :activities="stats?.recent_activities" :loading="loading" />
      </div>
    </div>
  </div>
</template>
