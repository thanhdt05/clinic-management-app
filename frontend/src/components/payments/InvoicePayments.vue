<script setup lang="ts">
import axios from 'axios'
import { computed, onMounted, ref, watch } from 'vue'
import { useToast } from 'primevue/usetoast'

import { paymentService } from '@/services/paymentService'
import { useAuthStore } from '@/stores/auth'

import type { ApiErrorResponse } from '@/types/auth'
import type { Invoice } from '@/types/invoice'
import type { Payment, PaymentMethod } from '@/types/payment'

const props = defineProps<{
  invoice: Invoice
}>()

const emit = defineEmits<{
  (event: 'invoice-updated'): void
}>()

const auth = useAuthStore()
const toast = useToast()

const payments = ref<Payment[]>([])

const loading = ref(false)
const saving = ref(false)

const paymentDialog = ref(false)
const captureDialog = ref(false)

const selectedPayment = ref<Payment | null>(null)

const errors = ref<Record<string, string[]>>({})

const form = ref({
  amount: null as number | null,
  method: 'paypal' as PaymentMethod,
  note: '',
})

const methodOptions = [
  { label: 'PayPal', value: 'paypal' },
  { label: 'Visa via PayPal', value: 'visa' },
]

const completedAmount = computed(() => {
  return payments.value
    .filter((payment) => payment.status === 'completed')
    .reduce((sum, payment) => sum + Number(payment.amount), 0)
})

const remainingAmount = computed(() => {
  return Math.max(Number(props.invoice.total) - completedAmount.value, 0)
})

const canCreatePayment = computed(() => {
  return (
    props.invoice.status === 'unpaid' && remainingAmount.value > 0 && auth.can('PAYMENTS.CREATE')
  )
})

onMounted(() => {
  loadPayments()
})

watch(
  () => props.invoice.id,
  () => {
    loadPayments()
  },
)

async function loadPayments() {
  loading.value = true

  try {
    const response = await paymentService.getByInvoice(props.invoice.id)
    payments.value = response.data
  } catch (error) {
    showApiError(error, 'Unable to load payments.')
  } finally {
    loading.value = false
  }
}

function openPayment() {
  errors.value = {}
  form.value = {
    amount: remainingAmount.value,
    method: 'paypal',
    note: '',
  }
  paymentDialog.value = true
}

async function createPayment() {
  errors.value = {}

  if (form.value.amount === null || form.value.amount <= 0 || !form.value.method) {
    return
  }

  saving.value = true

  try {
    const response = await paymentService.create(props.invoice.id, {
      amount: form.value.amount,
      method: form.value.method,
      note: form.value.note.trim() || null,
    })

    paymentDialog.value = false

    toast.add({
      severity: 'success',
      summary: 'Payment Created',
      detail: response.message,
      life: 3000,
    })

    if (response.data.approval_url) {
      window.open(response.data.approval_url, '_blank', 'noopener,noreferrer')
    }

    await loadPayments()
  } catch (error) {
    showApiError(error, 'Unable to create payment.')
  } finally {
    saving.value = false
  }
}

function confirmCapture(payment: Payment) {
  selectedPayment.value = payment
  captureDialog.value = true
}

async function capturePayment() {
  if (!selectedPayment.value) {
    return
  }

  saving.value = true

  try {
    const response = await paymentService.capture(selectedPayment.value.id)

    captureDialog.value = false

    toast.add({
      severity: 'success',
      summary: 'Payment Captured',
      detail: response.message,
      life: 3000,
    })

    await loadPayments()
    emit('invoice-updated')
  } catch (error) {
    showApiError(error, 'Unable to capture payment.')
  } finally {
    saving.value = false
  }
}

function fieldError(field: string) {
  return errors.value[field]?.[0]
}

function paymentMethodLabel(method: PaymentMethod) {
  return methodOptions.find((item) => item.value === method)?.label ?? method
}

function statusSeverity(status: Payment['status']) {
  switch (status) {
    case 'completed':
      return 'success'
    case 'pending':
      return 'warn'
    case 'failed':
      return 'danger'
    case 'cancelled':
      return 'secondary'
    default:
      return 'secondary'
  }
}

function formatMoney(value: number | string) {
  const formatted = Number(value).toLocaleString('vi-VN', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  })
  return `${formatted} ₫`
}

function formatUSD(value: number) {
  return `$${value.toFixed(2)} USD`
}

function formatDateTime(value: string | null) {
  if (!value) {
    return '—'
  }

  const date = new Date(value.replace(' ', 'T'))
  return Number.isNaN(date.getTime()) ? value : date.toLocaleString('vi-VN', { hour12: false })
}

function showApiError(error: unknown, fallback: string) {
  let message = fallback

  if (axios.isAxiosError<ApiErrorResponse>(error)) {
    errors.value = error.response?.data.errors ?? {}
    const firstError = Object.values(errors.value)[0]?.[0]
    message = error.response?.data.message ?? firstError ?? fallback
  }

  toast.add({
    severity: 'error',
    summary: 'Error',
    detail: message,
    life: 4000,
  })
}
</script>

<template>
  <div class="flex flex-col gap-5">
    <!-- Payment Summary Cards -->
    <div class="grid grid-cols-12 gap-4">
      <div
        class="col-span-12 md:col-span-4 p-4 bg-surface-50 dark:bg-surface-800 rounded-lg border border-surface"
      >
        <small class="text-muted-color uppercase font-semibold text-xs block mb-1"
          >Invoice Total</small
        >
        <div class="text-xl font-bold text-primary">
          {{ formatMoney(invoice.total) }}
        </div>
      </div>

      <div
        class="col-span-12 md:col-span-4 p-4 bg-surface-50 dark:bg-surface-800 rounded-lg border border-surface"
      >
        <small class="text-muted-color uppercase font-semibold text-xs block mb-1"
          >Paid Amount</small
        >
        <div class="text-xl font-bold text-green-600">
          {{ formatMoney(completedAmount) }}
        </div>
      </div>

      <div
        class="col-span-12 md:col-span-4 p-4 bg-surface-50 dark:bg-surface-800 rounded-lg border border-surface"
      >
        <small class="text-muted-color uppercase font-semibold text-xs block mb-1"
          >Available to Pay</small
        >
        <div
          class="text-xl font-bold"
          :class="remainingAmount > 0 ? 'text-orange-500' : 'text-muted-color'"
        >
          {{ formatMoney(remainingAmount) }}
        </div>
      </div>
    </div>

    <!-- Toolbar -->
    <Toolbar>
      <template #start>
        <div class="font-semibold text-base">Payment History</div>
      </template>

      <template #end>
        <Button
          v-if="canCreatePayment"
          label="New Payment"
          icon="pi pi-credit-card"
          size="small"
          @click="openPayment"
        />
      </template>
    </Toolbar>

    <DataTable :value="payments" data-key="id" :loading="loading" striped-rows>
      <template #empty> No payments recorded yet. </template>

      <Column header="Method" style="min-width: 11rem">
        <template #body="slotProps">
          <div class="flex items-center gap-2">
            <i class="pi pi-credit-card text-primary" />
            <span>{{ paymentMethodLabel(slotProps.data.method) }}</span>
          </div>
        </template>
      </Column>

      <Column header="Amount (₫)" style="min-width: 11rem">
        <template #body="slotProps">
          <span class="font-semibold">
            {{ formatMoney(slotProps.data.amount) }}
          </span>
        </template>
      </Column>

      <Column header="Status" style="min-width: 9rem">
        <template #body="slotProps">
          <Tag
            :value="slotProps.data.status.toUpperCase()"
            :severity="statusSeverity(slotProps.data.status)"
          />
        </template>
      </Column>

      <Column header="PayPal Order ID" style="min-width: 14rem">
        <template #body="slotProps">
          <span class="font-mono text-xs">
            {{ slotProps.data.provider_order_id ?? '—' }}
          </span>
        </template>
      </Column>

      <Column header="Paid At" style="min-width: 13rem">
        <template #body="slotProps">
          <span class="text-sm text-muted-color">
            {{ formatDateTime(slotProps.data.paid_at) }}
          </span>
        </template>
      </Column>

      <Column header="Note" style="min-width: 13rem">
        <template #body="slotProps">
          <span class="text-sm">{{ slotProps.data.note ?? '—' }}</span>
        </template>
      </Column>

      <Column :exportable="false" style="width: 9rem">
        <template #body="slotProps">
          <Button
            v-if="
              slotProps.data.status === 'pending' &&
              invoice.status === 'unpaid' &&
              auth.can('PAYMENTS.CAPTURE')
            "
            label="Capture"
            icon="pi pi-check"
            size="small"
            outlined
            @click="confirmCapture(slotProps.data)"
          />
        </template>
      </Column>
    </DataTable>

    <!-- CREATE PAYMENT -->
    <Dialog v-model:visible="paymentDialog" :style="{ width: '500px' }" header="New Payment" modal>
      <div class="flex flex-col gap-5">
        <Message severity="info">
          Remaining amount: <b>{{ formatMoney(remainingAmount) }}</b>
        </Message>

        <div>
          <label class="block font-bold mb-3"> Payment Method </label>
          <Select
            v-model="form.method"
            :options="methodOptions"
            option-label="label"
            option-value="value"
            fluid
            :invalid="!!fieldError('method')"
          />
          <small v-if="fieldError('method')" class="text-red-500">
            {{ fieldError('method') }}
          </small>
        </div>

        <div>
          <label class="block font-bold mb-3"> Amount (₫) </label>
          <InputNumber
            v-model="form.amount"
            :min="1"
            :max="remainingAmount"
            :min-fraction-digits="0"
            :max-fraction-digits="2"
            fluid
            :invalid="!!fieldError('amount')"
          />
          <small v-if="form.amount" class="text-muted-color block mt-1">
            ≈ <b>{{ formatUSD(form.amount / 25000) }}</b> (Exchange rate: 1 USD = 25,000 ₫)
          </small>
          <small v-if="fieldError('amount')" class="text-red-500">
            {{ fieldError('amount') }}
          </small>
        </div>

        <div>
          <label class="block font-bold mb-3"> Note </label>
          <Textarea
            v-model="form.note"
            rows="3"
            maxlength="500"
            fluid
            :invalid="!!fieldError('note')"
          />
          <small v-if="fieldError('note')" class="text-red-500">
            {{ fieldError('note') }}
          </small>
        </div>

        <Message severity="warn">
          After creating the payment, PayPal Sandbox will open in a new tab. Complete approval
          there, then return here and click Capture.
        </Message>
      </div>

      <template #footer>
        <Button
          label="Cancel"
          icon="pi pi-times"
          text
          :disabled="saving"
          @click="paymentDialog = false"
        />
        <Button
          label="Continue to PayPal"
          icon="pi pi-external-link"
          :loading="saving"
          @click="createPayment"
        />
      </template>
    </Dialog>

    <!-- CAPTURE -->
    <Dialog
      v-model:visible="captureDialog"
      :style="{ width: '470px' }"
      header="Capture Payment"
      modal
    >
      <div v-if="selectedPayment" class="flex flex-col gap-4">
        <div class="flex items-center gap-4">
          <i class="pi pi-exclamation-circle !text-3xl text-primary" />
          <span>
            Capture payment of <b>{{ formatMoney(selectedPayment.amount) }}</b> (~
            {{ formatUSD(Number(selectedPayment.amount) / 25000) }})?
          </span>
        </div>

        <Message severity="warn">
          Only capture after the customer has completed the PayPal Sandbox approval.
        </Message>
      </div>

      <template #footer>
        <Button
          label="Cancel"
          icon="pi pi-times"
          text
          :disabled="saving"
          @click="captureDialog = false"
        />
        <Button label="Capture" icon="pi pi-check" :loading="saving" @click="capturePayment" />
      </template>
    </Dialog>
  </div>
</template>
