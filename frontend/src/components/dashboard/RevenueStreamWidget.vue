<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { useLayout } from '@/layout/composables/layout'
import type { RevenueStreamItem } from '@/types/stats'

const props = defineProps<{
  revenueStream?: RevenueStreamItem[]
  loading?: boolean
}>()

const { layoutConfig, isDarkTheme } = useLayout()

const chartData = ref<any>(null)
const chartOptions = ref<any>(null)

function setChartData() {
  const documentStyle = getComputedStyle(document.documentElement)

  const items = props.revenueStream ?? []
  const labels = items.map((item) => item.month)
  const revenueData = items.map((item) => item.revenue)
  const discountData = items.map((item) => item.discount)

  const primary500 = documentStyle.getPropertyValue('--p-primary-500') || '#3B82F6'
  const primary200 = documentStyle.getPropertyValue('--p-primary-200') || '#93C5FD'

  return {
    labels: labels.length > 0 ? labels : ['M-5', 'M-4', 'M-3', 'M-2', 'M-1', 'This Month'],
    datasets: [
      {
        type: 'bar',
        label: 'Revenue (VND)',
        backgroundColor: primary500,
        borderRadius: { topLeft: 6, topRight: 6 },
        data: revenueData.length > 0 ? revenueData : [0, 0, 0, 0, 0, 0],
        barThickness: 28,
      },
      {
        type: 'bar',
        label: 'Discount (VND)',
        backgroundColor: primary200,
        borderRadius: { topLeft: 6, topRight: 6 },
        data: discountData.length > 0 ? discountData : [0, 0, 0, 0, 0, 0],
        barThickness: 28,
      },
    ],
  }
}

function setChartOptions() {
  const documentStyle = getComputedStyle(document.documentElement)
  const borderColor =
    documentStyle.getPropertyValue('--surface-border') ||
    documentStyle.getPropertyValue('--p-content-border-color') ||
    '#e2e8f0'
  const textMutedColor =
    documentStyle.getPropertyValue('--text-color-secondary') ||
    documentStyle.getPropertyValue('--p-text-muted-color') ||
    '#64748b'

  return {
    maintainAspectRatio: false,
    aspectRatio: 0.8,
    plugins: {
      legend: {
        labels: {
          color: textMutedColor,
        },
      },
      tooltip: {
        callbacks: {
          label: function (context: any) {
            let label = context.dataset.label || ''
            if (label) {
              label += ': '
            }
            if (context.parsed.y !== null) {
              label += new Intl.NumberFormat('vi-VN', {
                style: 'currency',
                currency: 'VND',
              }).format(context.parsed.y)
            }
            return label
          },
        },
      },
    },
    scales: {
      x: {
        ticks: {
          color: textMutedColor,
        },
        grid: {
          color: 'transparent',
          borderColor: 'transparent',
        },
      },
      y: {
        ticks: {
          color: textMutedColor,
          callback: function (value: any) {
            if (value >= 1000000) {
              return (value / 1000000).toFixed(1) + 'M'
            }
            if (value >= 1000) {
              return (value / 1000).toFixed(0) + 'k'
            }
            return value
          },
        },
        grid: {
          color: borderColor,
          borderColor: 'transparent',
          drawTicks: false,
        },
      },
    },
  }
}

watch(
  [
    () => layoutConfig.primary,
    () => layoutConfig.surface,
    () => layoutConfig.preset,
    isDarkTheme,
    () => props.revenueStream,
  ],
  () => {
    chartData.value = setChartData()
    chartOptions.value = setChartOptions()
  },
  { immediate: true },
)

onMounted(() => {
  chartData.value = setChartData()
  chartOptions.value = setChartOptions()
})
</script>

<template>
  <div class="card h-full">
    <div class="flex justify-between items-center mb-4">
      <div>
        <div class="font-semibold text-xl">Revenue Stream</div>
        <span class="text-sm text-muted-color">Monthly financial performance</span>
      </div>
      <Tag severity="info" value="Last 6 Months" />
    </div>

    <div v-if="loading" class="h-80 flex items-center justify-center">
      <ProgressSpinner style="width: 50px; height: 50px" strokeWidth="4" />
    </div>
    <div
      v-else-if="!revenueStream || revenueStream.length === 0"
      class="h-80 flex flex-col items-center justify-center text-muted-color"
    >
      <i class="pi pi-chart-bar text-4xl mb-2"></i>
      <span>No revenue data recorded yet for the last 6 months</span>
    </div>
    <Chart v-else type="bar" :data="chartData" :options="chartOptions" class="h-80" />
  </div>
</template>
