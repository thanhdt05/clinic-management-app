<script setup lang="ts">
import { useRouter } from 'vue-router'
import type { RecentInvoice } from '@/types/stats'

defineProps<{
  recentInvoices?: RecentInvoice[]
  loading?: boolean
}>()

const router = useRouter()

function formatCurrency(value?: string | number) {
  if (value === undefined || value === null) return '0 ₫'
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(Number(value))
}

function formatDate(dateStr?: string) {
  if (!dateStr) return '-'
  const date = new Date(dateStr)
  return date.toLocaleDateString('vi-VN', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function statusSeverity(status: string) {
  switch (status) {
    case 'paid':
      return 'success'
    case 'unpaid':
      return 'warn'
    case 'cancelled':
      return 'danger'
    default:
      return 'secondary'
  }
}
</script>

<template>
  <div class="card h-full">
    <div class="flex justify-between items-center mb-4">
      <div>
        <div class="font-semibold text-xl">Recent Invoices</div>
        <span class="text-sm text-muted-color">Latest clinic billing transactions</span>
      </div>
      <Button
        label="View All"
        icon="pi pi-arrow-right"
        iconPos="right"
        text
        size="small"
        @click="router.push('/invoices')"
      />
    </div>

    <DataTable
      :value="recentInvoices ?? []"
      :loading="loading"
      responsiveLayout="scroll"
      class="p-datatable-sm"
    >
      <template #empty>
        <div class="text-center py-6 text-muted-color">
          <i class="pi pi-receipt text-2xl mb-2"></i>
          <div>No recent invoices found</div>
        </div>
      </template>

      <Column field="invoice_code" header="Invoice Code" style="width: 25%">
        <template #body="{ data }">
          <span class="font-semibold text-primary">{{ data.invoice_code }}</span>
        </template>
      </Column>

      <Column header="Patient" style="width: 30%">
        <template #body="{ data }">
          <div class="font-medium">{{ data.examination?.patient?.full_name ?? 'Walk-in Patient' }}</div>
          <div class="text-xs text-muted-color">{{ data.examination?.patient?.code ?? '' }}</div>
        </template>
      </Column>

      <Column header="Total" style="width: 25%">
        <template #body="{ data }">
          <span class="font-semibold">{{ formatCurrency(data.total) }}</span>
        </template>
      </Column>

      <Column header="Status" style="width: 20%">
        <template #body="{ data }">
          <Tag :value="data.status.toUpperCase()" :severity="statusSeverity(data.status)" />
        </template>
      </Column>
    </DataTable>
  </div>
</template>
