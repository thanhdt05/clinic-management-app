<script setup lang="ts">
import axios from 'axios'
import { onMounted, ref } from 'vue'
import { useToast } from 'primevue/usetoast'

import { medicineService } from '@/services/medicineService'
import { useAuthStore } from '@/stores/auth'

import type { ApiErrorResponse } from '@/types/auth'
import type {
  CreateMedicinePayload,
  Medicine,
  MedicineStockStatus,
  PaginationMeta,
  UpdateMedicinePayload,
} from '@/types/medicine'

interface PageEvent {
  page: number
  rows: number
}

const auth = useAuthStore()
const toast = useToast()

const medicines = ref<Medicine[]>([])

const loading = ref(false)
const saving = ref(false)
const stockSaving = ref(false)
const detailLoading = ref(false)
const submitted = ref(false)

const medicineDialog = ref(false)
const detailDialog = ref(false)
const deleteMedicineDialog = ref(false)
const stockDialog = ref(false)

const selectedMedicine = ref<Medicine | null>(null)
const detailMedicine = ref<Medicine | null>(null)

const errors = ref<Record<string, string[]>>({})
const stockErrors = ref<Record<string, string[]>>({})
const stockStatus = ref<MedicineStockStatus | null>(null)

const meta = ref<PaginationMeta>({
  current_page: 1,
  per_page: 10,
  total: 0,
  last_page: 1,
})

const form = ref({
  code: '',
  name: '',
  unit: '',
  price: null as number | null,
  stock: 0,
  is_active: true,
})

const stockForm = ref({
  quantity: null as number | null,
})

const stockStatusOptions = [
  { label: 'In Stock', value: 'in_stock' },
  { label: 'Out of Stock', value: 'out_of_stock' },
]

onMounted(() => {
  loadMedicines()
})

function fieldError(field: string) {
  return errors.value[field]?.[0]
}

function stockFieldError(field: string) {
  return stockErrors.value[field]?.[0]
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

async function loadMedicines(page = 1, perPage = meta.value.per_page) {
  loading.value = true

  try {
    const response = await medicineService.getAll({
      page,
      per_page: perPage,
      ...(stockStatus.value ? { stock_status: stockStatus.value } : {}),
    })

    medicines.value = response.data
    meta.value = response.meta
  } catch (error) {
    showApiError(error, 'Unable to load medicines.')
  } finally {
    loading.value = false
  }
}

function applyFilter() {
  loadMedicines(1, meta.value.per_page)
}

function clearFilter() {
  stockStatus.value = null
  loadMedicines(1, meta.value.per_page)
}

function openNew() {
  selectedMedicine.value = null
  submitted.value = false
  errors.value = {}

  form.value = {
    code: '',
    name: '',
    unit: '',
    price: null,
    stock: 0,
    is_active: true,
  }

  medicineDialog.value = true
}

function editMedicine(medicine: Medicine) {
  selectedMedicine.value = medicine
  submitted.value = false
  errors.value = {}

  form.value = {
    code: medicine.code,
    name: medicine.name,
    unit: medicine.unit,
    price: Number(medicine.price),
    stock: medicine.stock,
    is_active: medicine.is_active,
  }

  medicineDialog.value = true
}

function hideDialog() {
  medicineDialog.value = false
  submitted.value = false
  errors.value = {}
}

async function viewMedicine(medicine: Medicine) {
  detailDialog.value = true
  detailMedicine.value = null
  detailLoading.value = true

  try {
    const response = await medicineService.getOne(medicine.id)
    detailMedicine.value = response.data
  } catch (error) {
    detailDialog.value = false
    showApiError(error, 'Unable to load medicine details.')
  } finally {
    detailLoading.value = false
  }
}

function openEditFromView(medicine: Medicine) {
  editMedicine(medicine)
  detailDialog.value = false
}

async function saveMedicine() {
  submitted.value = true
  errors.value = {}

  if (
    !form.value.code.trim() ||
    !form.value.name.trim() ||
    !form.value.unit.trim() ||
    form.value.price === null ||
    form.value.price < 0
  ) {
    return
  }

  if (!selectedMedicine.value && form.value.stock < 0) {
    return
  }

  saving.value = true

  try {
    if (selectedMedicine.value) {
      const payload: UpdateMedicinePayload = {
        code: form.value.code.trim(),
        name: form.value.name.trim(),
        unit: form.value.unit.trim(),
        price: form.value.price!,
        is_active: form.value.is_active,
      }

      const response = await medicineService.update(selectedMedicine.value.id, payload)
      toast.add({
        severity: 'success',
        summary: 'Successful',
        detail: response.message,
        life: 3000,
      })
    } else {
      const payload: CreateMedicinePayload = {
        code: form.value.code.trim(),
        name: form.value.name.trim(),
        unit: form.value.unit.trim(),
        price: form.value.price!,
        stock: form.value.stock,
        is_active: form.value.is_active,
      }

      const response = await medicineService.create(payload)
      toast.add({
        severity: 'success',
        summary: 'Successful',
        detail: response.message,
        life: 3000,
      })
    }

    medicineDialog.value = false
    await loadMedicines(selectedMedicine.value ? meta.value.current_page : 1)
  } catch (error) {
    showApiError(error, 'Unable to save medicine.')
  } finally {
    saving.value = false
  }
}

function confirmDeleteMedicine(medicine: Medicine) {
  selectedMedicine.value = medicine
  deleteMedicineDialog.value = true
}

async function deleteMedicine() {
  if (!selectedMedicine.value) {
    return
  }

  try {
    const response = await medicineService.remove(selectedMedicine.value.id)

    deleteMedicineDialog.value = false
    toast.add({
      severity: 'success',
      summary: 'Successful',
      detail: response.message,
      life: 3000,
    })

    const nextPage =
      medicines.value.length === 1 && meta.value.current_page > 1
        ? meta.value.current_page - 1
        : meta.value.current_page

    await loadMedicines(nextPage)
  } catch (error) {
    showApiError(error, 'Unable to delete medicine.')
  }
}

function openStockDialog(medicine: Medicine) {
  selectedMedicine.value = medicine
  stockErrors.value = {}
  stockForm.value = {
    quantity: null,
  }
  stockDialog.value = true
}

async function adjustStock() {
  if (
    !selectedMedicine.value ||
    stockForm.value.quantity === null ||
    stockForm.value.quantity === 0
  ) {
    return
  }

  stockErrors.value = {}
  stockSaving.value = true

  try {
    const response = await medicineService.adjustStock(selectedMedicine.value.id, {
      quantity: stockForm.value.quantity!,
    })

    stockDialog.value = false
    toast.add({
      severity: 'success',
      summary: 'Successful',
      detail: response.message,
      life: 3000,
    })

    await loadMedicines(meta.value.current_page)
  } catch (error) {
    if (axios.isAxiosError<ApiErrorResponse>(error)) {
      stockErrors.value = error.response?.data.errors ?? {}
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: error.response?.data.message ?? 'Unable to adjust stock.',
        life: 4000,
      })
      return
    }

    showApiError(error, 'Unable to adjust stock.')
  } finally {
    stockSaving.value = false
  }
}

function onPage(event: PageEvent) {
  loadMedicines(event.page + 1, event.rows)
}

function formatPrice(price: string | number) {
  return `${Number(price).toLocaleString('vi-VN')} ₫`
}
</script>

<template>
  <div>
    <Toast />

    <div class="card">
      <!-- Sakai CRUD Toolbar -->
      <Toolbar class="mb-6">
        <template #start>
          <Button
            v-if="auth.can('MEDICINES.CREATE')"
            label="New Medicine"
            icon="pi pi-plus"
            severity="secondary"
            @click="openNew"
          />
        </template>
      </Toolbar>

      <!-- Filters -->
      <div class="card mb-6 !p-4">
        <div class="flex flex-wrap gap-3 items-end">
          <div class="flex flex-col gap-2">
            <label class="font-medium">Stock Status</label>
            <Select
              v-model="stockStatus"
              :options="stockStatusOptions"
              option-label="label"
              option-value="value"
              show-clear
              placeholder="All Stock"
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

      <!-- Sakai DataTable -->
      <DataTable
        :value="medicines"
        data-key="id"
        lazy
        paginator
        striped-rows
        :rows="meta.per_page"
        :first="(meta.current_page - 1) * meta.per_page"
        :total-records="meta.total"
        :loading="loading"
        :rows-per-page-options="[10, 25, 50]"
        paginator-template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown CurrentPageReport"
        current-page-report-template="Showing {first} to {last} of {totalRecords} medicines"
        @page="onPage"
      >
        <template #header>
          <div class="flex items-center justify-between flex-wrap gap-2">
            <h4 class="m-0">Manage Medicines</h4>
            <span class="text-muted-color">{{ meta.total }} medicines</span>
          </div>
        </template>

        <template #empty> No medicines found. </template>

        <Column field="code" header="Code" style="min-width: 10rem" />

        <Column field="name" header="Name" style="min-width: 16rem">
          <template #body="slotProps">
            <span class="font-medium">{{ slotProps.data.name }}</span>
          </template>
        </Column>

        <Column field="unit" header="Unit" style="min-width: 8rem" />

        <Column header="Price" style="min-width: 10rem">
          <template #body="slotProps">
            {{ formatPrice(slotProps.data.price) }}
          </template>
        </Column>

        <Column header="Stock" style="min-width: 10rem">
          <template #body="slotProps">
            <Tag
              :value="`${slotProps.data.stock} ${slotProps.data.unit}`"
              :severity="slotProps.data.stock > 0 ? 'success' : 'danger'"
            />
          </template>
        </Column>

        <Column header="Status" style="min-width: 9rem">
          <template #body="slotProps">
            <Tag
              :value="slotProps.data.is_active ? 'ACTIVE' : 'INACTIVE'"
              :severity="slotProps.data.is_active ? 'success' : 'secondary'"
            />
          </template>
        </Column>

        <Column :exportable="false" style="min-width: 14rem">
          <template #body="slotProps">
            <Button
              v-if="auth.can('MEDICINES.FINDONE')"
              icon="pi pi-eye"
              outlined
              rounded
              severity="info"
              class="mr-2"
              aria-label="View details"
              @click="viewMedicine(slotProps.data)"
            />

            <Button
              v-if="auth.can('MEDICINES.ADJUSTSTOCK')"
              icon="pi pi-box"
              outlined
              rounded
              severity="warn"
              class="mr-2"
              aria-label="Adjust stock"
              @click="openStockDialog(slotProps.data)"
            />

            <Button
              v-if="auth.can('MEDICINES.UPDATE')"
              icon="pi pi-pencil"
              outlined
              rounded
              class="mr-2"
              aria-label="Edit medicine"
              @click="editMedicine(slotProps.data)"
            />

            <Button
              v-if="auth.can('MEDICINES.DELETE')"
              icon="pi pi-trash"
              outlined
              rounded
              severity="danger"
              aria-label="Delete medicine"
              @click="confirmDeleteMedicine(slotProps.data)"
            />
          </template>
        </Column>
      </DataTable>
    </div>

    <!-- Create/Edit Dialog -->
    <Dialog
      v-model:visible="medicineDialog"
      :style="{ width: '550px' }"
      :header="selectedMedicine ? 'Edit Medicine' : 'Medicine Details'"
      modal
    >
      <div class="grid grid-cols-12 gap-4">
        <div class="col-span-12 md:col-span-5">
          <label for="medicine-code" class="block font-bold mb-3"> Code </label>
          <InputText
            id="medicine-code"
            v-model.trim="form.code"
            autofocus
            fluid
            :invalid="(submitted && !form.code) || !!fieldError('code')"
          />
          <small v-if="fieldError('code')" class="text-red-500">
            {{ fieldError('code') }}
          </small>
        </div>

        <div class="col-span-12 md:col-span-7">
          <label for="medicine-name" class="block font-bold mb-3"> Name </label>
          <InputText
            id="medicine-name"
            v-model.trim="form.name"
            fluid
            :invalid="(submitted && !form.name) || !!fieldError('name')"
          />
          <small v-if="fieldError('name')" class="text-red-500">
            {{ fieldError('name') }}
          </small>
        </div>

        <div class="col-span-12 md:col-span-4">
          <label for="medicine-unit" class="block font-bold mb-3"> Unit </label>
          <InputText
            id="medicine-unit"
            v-model.trim="form.unit"
            placeholder="tablet, box..."
            fluid
            :invalid="(submitted && !form.unit) || !!fieldError('unit')"
          />
          <small v-if="fieldError('unit')" class="text-red-500">
            {{ fieldError('unit') }}
          </small>
        </div>

        <div class="col-span-12 md:col-span-4">
          <label for="medicine-price" class="block font-bold mb-3"> Price </label>
          <InputNumber
            id="medicine-price"
            v-model="form.price"
            :min="0"
            :min-fraction-digits="0"
            :max-fraction-digits="2"
            fluid
            :invalid="!!fieldError('price')"
          />
          <small v-if="fieldError('price')" class="text-red-500">
            {{ fieldError('price') }}
          </small>
        </div>

        <!-- Initial stock only when creating -->
        <div class="col-span-12 md:col-span-4">
          <label for="medicine-stock" class="block font-bold mb-3"> Stock </label>
          <InputNumber
            id="medicine-stock"
            v-model="form.stock"
            :min="0"
            :use-grouping="false"
            :disabled="!!selectedMedicine"
            fluid
            :invalid="!!fieldError('stock')"
          />
          <small v-if="selectedMedicine" class="text-muted-color">
            Use Adjust Stock to change inventory.
          </small>
          <small v-if="fieldError('stock')" class="text-red-500">
            {{ fieldError('stock') }}
          </small>
        </div>

        <div class="col-span-12">
          <div class="flex items-center gap-3">
            <ToggleSwitch v-model="form.is_active" />
            <div>
              <div class="font-medium">Active Medicine</div>
              <small class="text-muted-color">
                Inactive medicines cannot be used for new prescriptions.
              </small>
            </div>
          </div>
          <small v-if="fieldError('is_active')" class="text-red-500">
            {{ fieldError('is_active') }}
          </small>
        </div>
      </div>

      <template #footer>
        <Button label="Cancel" icon="pi pi-times" text :disabled="saving" @click="hideDialog" />
        <Button label="Save" icon="pi pi-check" :loading="saving" @click="saveMedicine" />
      </template>
    </Dialog>

    <!-- View Medicine Details Dialog -->
    <Dialog
      v-model:visible="detailDialog"
      :style="{ width: '550px' }"
      header="Medicine Details"
      modal
    >
      <div v-if="detailLoading" class="flex justify-center py-8">
        <ProgressSpinner />
      </div>

      <div v-else-if="detailMedicine" class="flex flex-col gap-5">
        <div class="flex items-center gap-4 pb-4 border-b border-surface">
          <div
            class="w-14 h-14 rounded-full bg-primary/10 text-primary flex items-center justify-center text-xl font-bold shrink-0"
          >
            <i class="pi pi-box text-2xl" />
          </div>
          <div class="flex flex-col">
            <span class="text-lg font-semibold">{{ detailMedicine.name }}</span>
            <span class="text-sm text-muted-color">
              {{ detailMedicine.code }} · {{ detailMedicine.unit }}
            </span>
            <div class="mt-1">
              <Tag
                :value="detailMedicine.is_active ? 'ACTIVE' : 'INACTIVE'"
                :severity="detailMedicine.is_active ? 'success' : 'secondary'"
              />
            </div>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <span class="text-xs text-muted-color uppercase font-semibold block mb-1">Code</span>
            <span class="font-medium font-mono text-sm">{{ detailMedicine.code }}</span>
          </div>

          <div>
            <span class="text-xs text-muted-color uppercase font-semibold block mb-1">Unit</span>
            <span class="font-medium text-sm">{{ detailMedicine.unit }}</span>
          </div>

          <div>
            <span class="text-xs text-muted-color uppercase font-semibold block mb-1">Price</span>
            <span class="font-medium text-sm text-primary font-semibold">
              {{ formatPrice(detailMedicine.price) }}
            </span>
          </div>

          <div>
            <span class="text-xs text-muted-color uppercase font-semibold block mb-1">Stock</span>
            <div>
              <Tag
                :value="`${detailMedicine.stock} ${detailMedicine.unit}`"
                :severity="detailMedicine.stock > 0 ? 'success' : 'danger'"
              />
            </div>
          </div>

          <div>
            <span class="text-xs text-muted-color uppercase font-semibold block mb-1">
              Created At
            </span>
            <span class="text-sm text-muted-color">{{ detailMedicine.created_at ?? '—' }}</span>
          </div>

          <div>
            <span class="text-xs text-muted-color uppercase font-semibold block mb-1">
              Updated At
            </span>
            <span class="text-sm text-muted-color">{{ detailMedicine.updated_at ?? '—' }}</span>
          </div>
        </div>
      </div>

      <template #footer>
        <Button label="Close" icon="pi pi-times" text @click="detailDialog = false" />
        <Button
          v-if="auth.can('MEDICINES.UPDATE') && detailMedicine"
          label="Edit"
          icon="pi pi-pencil"
          @click="openEditFromView(detailMedicine)"
        />
      </template>
    </Dialog>

    <!-- Adjust Stock Dialog -->
    <Dialog
      v-model:visible="stockDialog"
      :style="{ width: '450px' }"
      header="Adjust Stock"
      modal
    >
      <div v-if="selectedMedicine" class="flex flex-col gap-5">
        <div class="p-4 border border-surface rounded">
          <div class="font-medium">{{ selectedMedicine.name }}</div>
          <div class="text-muted-color mt-1">
            Current stock:
            <b>{{ selectedMedicine.stock }} {{ selectedMedicine.unit }}</b>
          </div>
        </div>

        <div>
          <label for="stock-quantity" class="block font-bold mb-3"> Quantity Change </label>
          <InputNumber
            id="stock-quantity"
            v-model="stockForm.quantity"
            :use-grouping="false"
            show-buttons
            fluid
            :invalid="!!stockFieldError('quantity')"
          />
          <small class="block text-muted-color mt-2">
            Positive values add stock. Negative values remove stock.
          </small>
          <small v-if="stockFieldError('quantity')" class="block text-red-500 mt-1">
            {{ stockFieldError('quantity') }}
          </small>
        </div>

        <Message v-if="stockForm.quantity" severity="info">
          New stock: {{ selectedMedicine.stock + stockForm.quantity }} {{ selectedMedicine.unit }}
        </Message>
      </div>

      <template #footer>
        <Button
          label="Cancel"
          icon="pi pi-times"
          text
          :disabled="stockSaving"
          @click="stockDialog = false"
        />
        <Button
          label="Adjust"
          icon="pi pi-check"
          :loading="stockSaving"
          :disabled="!stockForm.quantity"
          @click="adjustStock"
        />
      </template>
    </Dialog>

    <!-- Sakai delete confirm -->
    <Dialog
      v-model:visible="deleteMedicineDialog"
      :style="{ width: '450px' }"
      header="Confirm"
      modal
    >
      <div class="flex items-center gap-4">
        <i class="pi pi-exclamation-triangle !text-3xl text-red-500" />
        <span v-if="selectedMedicine">
          Are you sure you want to delete <b>{{ selectedMedicine.name }}</b>?
        </span>
      </div>

      <template #footer>
        <Button label="No" icon="pi pi-times" text @click="deleteMedicineDialog = false" />
        <Button label="Yes" icon="pi pi-check" severity="danger" @click="deleteMedicine" />
      </template>
    </Dialog>
  </div>
</template>
