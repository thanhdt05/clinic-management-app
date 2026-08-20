<script setup lang="ts">
import type { RecentActivity } from '@/types/stats'

defineProps<{
  activities?: RecentActivity[]
  loading?: boolean
}>()

function formatTime(dateStr?: string) {
  if (!dateStr) return ''
  const date = new Date(dateStr)
  const now = new Date()
  const diffInMinutes = Math.floor((now.getTime() - date.getTime()) / (1000 * 60))

  if (diffInMinutes < 1) return 'Just now'
  if (diffInMinutes < 60) return `${diffInMinutes}m ago`
  const diffInHours = Math.floor(diffInMinutes / 60)
  if (diffInHours < 24) return `${diffInHours}h ago`
  return date.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' })
}

function getActionMeta(action: string) {
  if (action.includes('PAYMENT') || action.includes('INVOICE')) {
    return { icon: 'pi pi-dollar', bg: 'bg-green-100 dark:bg-green-400/10', color: 'text-green-500' }
  }
  if (action.includes('MEDICINE')) {
    return { icon: 'pi pi-box', bg: 'bg-orange-100 dark:bg-orange-400/10', color: 'text-orange-500' }
  }
  if (action.includes('APPOINTMENT')) {
    return { icon: 'pi pi-calendar', bg: 'bg-blue-100 dark:bg-blue-400/10', color: 'text-blue-500' }
  }
  if (action.includes('EXAMINATION') || action.includes('PRESCRIPTION')) {
    return { icon: 'pi pi-file-edit', bg: 'bg-purple-100 dark:bg-purple-400/10', color: 'text-purple-500' }
  }
  return { icon: 'pi pi-bell', bg: 'bg-cyan-100 dark:bg-cyan-400/10', color: 'text-cyan-500' }
}

function formatActionText(activity: RecentActivity) {
  const action = activity.action.toLowerCase().replace(/_/g, ' ')
  return action.charAt(0).toUpperCase() + action.slice(1)
}
</script>

<template>
  <div class="card h-full">
    <div class="flex items-center justify-between mb-4">
      <div>
        <div class="font-semibold text-xl">Recent Activity Logs</div>
        <span class="text-sm text-muted-color">Clinic system audit trail</span>
      </div>
      <Tag severity="secondary" value="Audit" />
    </div>

    <div v-if="loading" class="py-10 flex justify-center">
      <ProgressSpinner style="width: 40px; height: 40px" />
    </div>
    <div v-else-if="!activities || activities.length === 0" class="py-10 text-center text-muted-color">
      <i class="pi pi-history text-3xl mb-2"></i>
      <div>No recent activity logs recorded</div>
    </div>
    <ul v-else class="p-0 m-0 list-none max-h-96 overflow-y-auto pr-1">
      <li
        v-for="act in activities"
        :key="act.id"
        class="flex items-center py-3 border-b border-surface last:border-b-0 gap-3"
      >
        <div
          :class="[getActionMeta(act.action).bg, getActionMeta(act.action).color]"
          class="w-10 h-10 flex items-center justify-center rounded-full shrink-0"
        >
          <i :class="[getActionMeta(act.action).icon, 'text-lg']"></i>
        </div>
        <div class="flex-1 min-w-0">
          <div class="text-sm font-medium text-surface-900 dark:text-surface-0 truncate">
            <span class="font-semibold text-primary">{{ act.user?.name ?? 'System' }}</span>
            <span class="text-muted-color"> performed </span>
            <span>{{ formatActionText(act) }}</span>
          </div>
          <div v-if="act.meta && Object.keys(act.meta).length > 0" class="text-xs text-muted-color truncate mt-0.5">
            {{ JSON.stringify(act.meta).slice(0, 60) }}
          </div>
        </div>
        <div class="text-xs text-muted-color shrink-0">
          {{ formatTime(act.created_at) }}
        </div>
      </li>
    </ul>
  </div>
</template>
