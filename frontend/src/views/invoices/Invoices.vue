<script setup lang="ts">
import axios from 'axios'
import { onMounted, ref } from 'vue'
import { useToast } from 'primevue/usetoast'

import { examinationService } from '@/services/examinationService'
import { invoiceService } from '@/services/invoiceService'
import { useAuthStore } from '@/stores/auth'

import type { ApiErrorResponse } from '@/types/auth'
import type { Examination } from '@/types/examination'
import type { Invoice, InvoiceStatus, PaginationMeta } from '@/types/invoice'

import InvoicePayments from '@/components/payments/InvoicePayments.vue'

interface PageEvent {
  page: number
  rows: number
}

const auth = useAuthStore()
const toast = useToast()

const invoices = ref<Invoice[]>([])
const availableExaminations = ref<Examination[]>([])

const loading = ref(false)
const catalogLoading = ref(false)
const saving = ref(false)
const detailLoading = ref(false)
const submitted = ref(false)

const invoiceDialog = ref(false)
const detailDialog = ref(false)
const discountDialog = ref(false)
const cancelDialog = ref(false)

const selectedInvoice = ref<Invoice | null>(null)
const detailInvoice = ref<Invoice | null>(null)

const errors = ref<Record<string, string[]>>({})
const statusFilter = ref<InvoiceStatus | null>(null)

const meta = ref<PaginationMeta>({
  current_page: 1,
  per_page: 10,
  total: 0,
  last_page: 1,
})

const form = ref({
  examination_id: null as number | null,
  discount: 0,
})

const discountForm = ref({
  discount: 0,
})

const statusOptions = [
  { label: 'Unpaid', value: 'unpaid' },
  { label: 'Paid', value: 'paid' },
  { label: 'Cancelled', value: 'cancelled' },
]

onMounted(() => {
  loadInvoices()
})

async function loadInvoices(page = 1, perPage = meta.value.per_page) {
  loading.value = true

  try {
    const response = await invoiceService.getAll({
      page,
      per_page: perPage,
      ...(statusFilter.value ? { status: statusFilter.value } : {}),
    })

    invoices.value = response.data
    meta.value = response.meta
  } catch (error) {
    showApiError(error, 'Unable to load invoices.')
  } finally {
    loading.value = false
  }
}

function applyFilter() {
  loadInvoices(1, meta.value.per_page)
}

function clearFilter() {
  statusFilter.value = null
  loadInvoices(1, meta.value.per_page)
}

async function loadAvailableExaminations() {
  catalogLoading.value = true

  try {
    const examinations: Examination[] = []
    let page = 1
    let lastPage = 1

    do {
      const response = await examinationService.getAll({
        page,
        per_page: 100,
      })

      examinations.push(...response.data)
      lastPage = response.meta.last_page
      page++
    } while (page <= lastPage)

    const existingInvoices: Invoice[] = []
    page = 1
    lastPage = 1

    do {
      const response = await invoiceService.getAll({
        page,
        per_page: 100,
      })

      existingInvoices.push(...response.data)
      lastPage = response.meta.last_page
      page++
    } while (page <= lastPage)

    const invoicedExaminationIds = new Set(
      existingInvoices.map((invoice) => invoice.examination.id),
    )

    availableExaminations.value = examinations.filter(
      (examination) => !invoicedExaminationIds.has(examination.id),
    )
  } catch (error) {
    showApiError(error, 'Unable to load examinations.')
  } finally {
    catalogLoading.value = false
  }
}

async function openNew() {
  selectedInvoice.value = null
  submitted.value = false
  errors.value = {}

  form.value = {
    examination_id: null,
    discount: 0,
  }

  invoiceDialog.value = true
  await loadAvailableExaminations()
}

async function saveInvoice() {
  submitted.value = true
  errors.value = {}

  if (!form.value.examination_id || form.value.discount < 0) {
    return
  }

  saving.value = true

  try {
    const response = await invoiceService.create({
      examination_id: form.value.examination_id,
      discount: form.value.discount,
    })

    invoiceDialog.value = false
    toast.add({
      severity: 'success',
      summary: 'Successful',
      detail: response.message,
      life: 3000,
    })

    await loadInvoices(1)
  } catch (error) {
    showApiError(error, 'Unable to save invoice.')
  } finally {
    saving.value = false
  }
}

async function openDetails(invoice: Invoice) {
  detailDialog.value = true
  detailInvoice.value = null
  detailLoading.value = true

  try {
    const response = await invoiceService.getOne(invoice.id)
    detailInvoice.value = response.data
  } catch (error) {
    detailDialog.value = false
    showApiError(error, 'Unable to load invoice details.')
  } finally {
    detailLoading.value = false
  }
}

function openDiscount(invoice: Invoice) {
  selectedInvoice.value = invoice
  errors.value = {}

  discountForm.value = {
    discount: Number(invoice.discount),
  }

  discountDialog.value = true
}

async function saveDiscount() {
  if (!selectedInvoice.value) {
    return
  }

  if (discountForm.value.discount < 0) {
    return
  }

  saving.value = true
  errors.value = {}

  try {
    const response = await invoiceService.update(selectedInvoice.value.id, {
      discount: discountForm.value.discount,
    })

    discountDialog.value = false
    toast.add({
      severity: 'success',
      summary: 'Successful',
      detail: response.message,
      life: 3000,
    })

    await loadInvoices(meta.value.current_page)
  } catch (error) {
    showApiError(error, 'Unable to save discount.')
  } finally {
    saving.value = false
  }
}

async function handlePaymentInvoiceUpdated() {
  if (!detailInvoice.value) {
    return
  }

  const response = await invoiceService.getOne(detailInvoice.value.id)

  detailInvoice.value = response.data

  await loadInvoices(meta.value.current_page)
}

function confirmCancel(invoice: Invoice) {
  selectedInvoice.value = invoice
  cancelDialog.value = true
}

async function cancelInvoice() {
  if (!selectedInvoice.value) {
    return
  }

  saving.value = true

  try {
    const response = await invoiceService.cancel(selectedInvoice.value.id)
    cancelDialog.value = false
    toast.add({
      severity: 'success',
      summary: 'Successful',
      detail: response.message,
      life: 3000,
    })

    await loadInvoices(meta.value.current_page)
  } catch (error) {
    showApiError(error, 'Unable to cancel invoice.')
  } finally {
    saving.value = false
  }
}

function onPage(event: PageEvent) {
  loadInvoices(event.page + 1, event.rows)
}

function fieldError(field: string) {
  return errors.value[field]?.[0]
}

function statusLabel(status: InvoiceStatus) {
  return statusOptions.find((item) => item.value === status)?.label ?? status
}

function statusSeverity(status: InvoiceStatus) {
  switch (status) {
    case 'unpaid':
      return 'warn'
    case 'paid':
      return 'success'
    case 'cancelled':
      return 'danger'
    default:
      return 'secondary'
  }
}

function examinationLabel(examination: Examination) {
  return [
    examination.patient_id.code,
    examination.patient_id.full_name,
    formatDateTime(examination.examined_at),
  ].join(' — ')
}

function formatMoney(value: string | number) {
  const formatted = Number(value).toLocaleString('vi-VN', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  })
  return `${formatted} ₫`
}

function formatDateTime(value: string) {
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
  <div>
    <Toast />

    <div class="card">
      <Toolbar class="mb-6">
        <template #start>
          <Button
            v-if="auth.can('INVOICES.CREATE')"
            label="New Invoice"
            icon="pi pi-plus"
            severity="secondary"
            @click="openNew"
          />
        </template>
      </Toolbar>

      <!-- Filter -->
      <div class="card mb-6 !p-4">
        <div class="flex flex-wrap gap-3 items-end">
          <div class="flex flex-col gap-2">
            <label class="font-medium">Status</label>
            <Select
              v-model="statusFilter"
              :options="statusOptions"
              option-label="label"
              option-value="value"
              show-clear
              placeholder="All Statuses"
              class="w-56"
            />
          </div>

          <Button label="Filter" icon="pi pi-filter" @click="applyFilter" />
          <Button
            label="Clear"
            icon="pi pi-filter-slash"
            severity="secondary"
            text
            @click="clearFilter"
          />
        </div>
      </div>

      <!-- DataTable -->
      <DataTable
        :value="invoices"
        data-key="id"
        lazy
        paginator
        striped-rows
        :loading="loading"
        :rows="meta.per_page"
        :first="(meta.current_page - 1) * meta.per_page"
        :total-records="meta.total"
        :rows-per-page-options="[10, 25, 50]"
        paginator-template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown CurrentPageReport"
        current-page-report-template="Showing {first} to {last} of {totalRecords} invoices"
        @page="onPage"
      >
        <template #header>
          <div class="flex items-center justify-between flex-wrap gap-2">
            <h4 class="m-0">Manage Invoices</h4>
            <span class="text-muted-color">{{ meta.total }} invoices</span>
          </div>
        </template>

        <template #empty> No invoices found. </template>

        <Column field="invoice_code" header="Invoice Code" style="min-width: 12rem">
          <template #body="slotProps">
            <span class="font-semibold font-mono text-primary">
              {{ slotProps.data.invoice_code }}
            </span>
          </template>
        </Column>

        <Column header="Patient" style="min-width: 15rem">
          <template #body="slotProps">
            <div class="flex flex-col">
              <span class="font-medium">
                {{ slotProps.data.examination?.patient?.full_name ?? '—' }}
              </span>
              <small class="text-muted-color">
                {{ slotProps.data.examination?.patient?.code ?? '' }}
              </small>
            </div>
          </template>
        </Column>

        <Column header="Subtotal" style="min-width: 10rem">
          <template #body="slotProps">
            {{ formatMoney(slotProps.data.subtotal) }}
          </template>
        </Column>

        <Column header="Discount" style="min-width: 10rem">
          <template #body="slotProps">
            <span :class="Number(slotProps.data.discount) > 0 ? 'text-green-600 font-medium' : ''">
              {{ formatMoney(slotProps.data.discount) }}
            </span>
          </template>
        </Column>

        <Column header="Total" style="min-width: 11rem">
          <template #body="slotProps">
            <span class="font-bold text-primary">
              {{ formatMoney(slotProps.data.total) }}
            </span>
          </template>
        </Column>

        <Column header="Status" style="min-width: 9rem">
          <template #body="slotProps">
            <Tag
              :value="statusLabel(slotProps.data.status)"
              :severity="statusSeverity(slotProps.data.status)"
            />
          </template>
        </Column>

        <Column header="Issued At" style="min-width: 13rem">
          <template #body="slotProps">
            {{ formatDateTime(slotProps.data.issued_at) }}
          </template>
        </Column>

        <Column :exportable="false" style="min-width: 12rem">
          <template #body="slotProps">
            <Button
              v-if="auth.can('INVOICES.FINDONE')"
              icon="pi pi-eye"
              outlined
              rounded
              severity="info"
              class="mr-2"
              @click="openDetails(slotProps.data)"
            />

            <!-- Discount only while unpaid -->
            <Button
              v-if="slotProps.data.status === 'unpaid' && auth.can('INVOICES.UPDATE')"
              icon="pi pi-pencil"
              outlined
              rounded
              class="mr-2"
              aria-label="Edit discount"
              @click="openDiscount(slotProps.data)"
            />

            <!-- Cancel only unpaid -->
            <Button
              v-if="slotProps.data.status === 'unpaid' && auth.can('INVOICES.UPDATESTATUS')"
              icon="pi pi-times"
              outlined
              rounded
              severity="danger"
              aria-label="Cancel invoice"
              @click="confirmCancel(slotProps.data)"
            />
          </template>
        </Column>
      </DataTable>
    </div>

    <!-- CREATE INVOICE -->
    <Dialog v-model:visible="invoiceDialog" :style="{ width: '600px' }" header="New Invoice" modal>
      <div class="flex flex-col gap-6">
        <div>
          <label class="block font-bold mb-3"> Examination </label>
          <Select
            v-model="form.examination_id"
            :options="availableExaminations"
            option-value="id"
            filter
            fluid
            :loading="catalogLoading"
            placeholder="Select examination"
            :invalid="(submitted && !form.examination_id) || !!fieldError('examination_id')"
          >
            <template #value="slotProps">
              <span v-if="slotProps.value">
                {{
                  examinationLabel(
                    availableExaminations.find((item) => item.id === slotProps.value)!,
                  )
                }}
              </span>
              <span v-else> Select examination </span>
            </template>

            <template #option="slotProps">
              <div class="flex flex-col">
                <span class="font-medium">
                  {{ slotProps.option.patient_id.full_name }}
                </span>
                <small class="text-muted-color">
                  {{ slotProps.option.patient_id.code }} · Examination #{{ slotProps.option.id }} ·
                  {{ formatDateTime(slotProps.option.examined_at) }}
                </small>
              </div>
            </template>
          </Select>

          <small v-if="fieldError('examination_id')" class="text-red-500">
            {{ fieldError('examination_id') }}
          </small>
          <small v-else-if="submitted && !form.examination_id" class="text-red-500">
            Examination is required.
          </small>

          <Message
            v-if="!catalogLoading && availableExaminations.length === 0"
            severity="info"
            class="mt-3"
          >
            No examinations are available for invoicing.
          </Message>
        </div>

        <div>
          <label class="block font-bold mb-3"> Discount (₫) </label>
          <InputNumber
            v-model="form.discount"
            :min="0"
            :min-fraction-digits="0"
            :max-fraction-digits="2"
            fluid
            :invalid="!!fieldError('discount')"
          />

          <small v-if="fieldError('discount')" class="text-red-500">
            {{ fieldError('discount') }}
          </small>
          <small class="block text-muted-color mt-2">
            Medicine cost and consultation fee will be automatically calculated by the server.
          </small>
        </div>
      </div>

      <template #footer>
        <Button label="Cancel" icon="pi pi-times" text @click="invoiceDialog = false" />
        <Button
          label="Create"
          icon="pi pi-check"
          :loading="saving"
          :disabled="availableExaminations.length === 0"
          @click="saveInvoice"
        />
      </template>
    </Dialog>

    <!-- DETAIL -->
    <Dialog
      v-model:visible="detailDialog"
      :style="{ width: '650px' }"
      header="Invoice Details"
      modal
    >
      <div v-if="detailLoading" class="flex justify-center py-8">
        <ProgressSpinner />
      </div>

      <div v-else-if="detailInvoice" class="flex flex-col gap-5">
        <!-- Avatar Header -->
        <div class="flex items-center justify-between pb-4 border-b border-surface">
          <div class="flex items-center gap-4">
            <div
              class="w-14 h-14 rounded-full bg-primary/10 text-primary flex items-center justify-center text-xl font-bold shrink-0"
            >
              {{ detailInvoice.examination?.patient?.full_name?.charAt(0).toUpperCase() ?? 'P' }}
            </div>
            <div class="flex flex-col">
              <span class="text-lg font-semibold">
                {{ detailInvoice.examination?.patient?.full_name ?? '—' }}
              </span>
              <span class="text-sm text-muted-color">
                {{ detailInvoice.examination?.patient?.code ?? '—' }} · Examination #{{
                  detailInvoice.examination?.id
                }}
              </span>
              <div class="mt-1">
                <span class="font-mono text-xs font-semibold text-primary">
                  {{ detailInvoice.invoice_code }}
                </span>
              </div>
            </div>
          </div>

          <Tag
            :value="statusLabel(detailInvoice.status)"
            :severity="statusSeverity(detailInvoice.status)"
          />
        </div>

        <!-- 2-Col Info Grid -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <span class="text-xs text-muted-color uppercase font-semibold block mb-1">
              Invoice Code
            </span>
            <span class="font-medium font-mono text-sm">
              {{ detailInvoice.invoice_code }}
            </span>
          </div>

          <div>
            <span class="text-xs text-muted-color uppercase font-semibold block mb-1">
              Examination ID
            </span>
            <span class="font-medium font-mono text-sm">
              #{{ detailInvoice.examination?.id }}
            </span>
          </div>

          <div>
            <span class="text-xs text-muted-color uppercase font-semibold block mb-1">
              Issued At
            </span>
            <span class="text-sm text-muted-color">
              {{ formatDateTime(detailInvoice.issued_at) }}
            </span>
          </div>

          <div>
            <span class="text-xs text-muted-color uppercase font-semibold block mb-1">
              Status
            </span>
            <span class="text-sm capitalize font-medium">
              {{ detailInvoice.status }}
            </span>
          </div>
        </div>

        <!-- Financial Summary Breakdown Box -->
        <div class="p-4 bg-surface-50 dark:bg-surface-800 rounded-lg flex flex-col gap-3">
          <div class="flex justify-between items-center text-sm">
            <span class="text-muted-color">Subtotal</span>
            <span class="font-medium">{{ formatMoney(detailInvoice.subtotal) }}</span>
          </div>

          <div class="flex justify-between items-center text-sm">
            <span class="text-muted-color">Discount</span>
            <span :class="Number(detailInvoice.discount) > 0 ? 'text-green-600 font-medium' : ''">
              - {{ formatMoney(detailInvoice.discount) }}
            </span>
          </div>

          <div
            class="border-t border-surface-200 dark:border-surface-700 pt-3 flex justify-between items-center"
          >
            <span class="font-bold text-base">Total Amount</span>
            <span class="text-xl font-bold text-primary">{{
              formatMoney(detailInvoice.total)
            }}</span>
          </div>
        </div>

        <Divider />

        <InvoicePayments
          v-if="detailInvoice && auth.can('PAYMENTS.FINDALL')"
          :invoice="detailInvoice"
          @invoice-updated="handlePaymentInvoiceUpdated"
        />
      </div>

      <template #footer>
        <Button label="Close" icon="pi pi-times" text @click="detailDialog = false" />
      </template>
    </Dialog>

    <!-- EDIT DISCOUNT -->
    <Dialog
      v-model:visible="discountDialog"
      :style="{ width: '450px' }"
      header="Edit Discount"
      modal
    >
      <div v-if="selectedInvoice" class="flex flex-col gap-5">
        <div>
          <span class="text-xs text-muted-color uppercase font-semibold block mb-1">Invoice</span>
          <div class="font-semibold font-mono text-primary">
            {{ selectedInvoice.invoice_code }}
          </div>
        </div>

        <div>
          <span class="text-xs text-muted-color uppercase font-semibold block mb-1">Subtotal</span>
          <div class="font-medium">
            {{ formatMoney(selectedInvoice.subtotal) }}
          </div>
        </div>

        <div>
          <label class="block font-bold mb-3"> Discount (₫) </label>
          <InputNumber
            v-model="discountForm.discount"
            :min="0"
            :max="Number(selectedInvoice.subtotal)"
            :min-fraction-digits="0"
            :max-fraction-digits="2"
            fluid
            :invalid="!!fieldError('discount')"
          />

          <small v-if="fieldError('discount')" class="text-red-500">
            {{ fieldError('discount') }}
          </small>
        </div>

        <Message severity="info">
          New total: {{ formatMoney(Number(selectedInvoice.subtotal) - discountForm.discount) }}
        </Message>
      </div>

      <template #footer>
        <Button label="Cancel" icon="pi pi-times" text @click="discountDialog = false" />
        <Button label="Save" icon="pi pi-check" :loading="saving" @click="saveDiscount" />
      </template>
    </Dialog>

    <!-- CANCEL -->
    <Dialog v-model:visible="cancelDialog" :style="{ width: '450px' }" header="Confirm" modal>
      <div class="flex items-center gap-4">
        <i class="pi pi-exclamation-triangle !text-3xl text-red-500" />
        <span v-if="selectedInvoice">
          Cancel invoice <b>{{ selectedInvoice.invoice_code }}</b
          >?
        </span>
      </div>

      <Message severity="warn" class="mt-4">
        A cancelled invoice cannot be edited or paid.
      </Message>

      <template #footer>
        <Button label="No" icon="pi pi-times" text @click="cancelDialog = false" />
        <Button
          label="Yes, Cancel"
          icon="pi pi-times"
          severity="danger"
          :loading="saving"
          @click="cancelInvoice"
        />
      </template>
    </Dialog>
  </div>
</template>
