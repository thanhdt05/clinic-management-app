<script setup lang="ts">
import axios from 'axios'
import { computed, onMounted, ref } from 'vue'
import { useToast } from 'primevue/usetoast'

import { doctorService } from '@/services/doctorService'
import { examinationService } from '@/services/examinationService'
import { medicineService } from '@/services/medicineService'
import { prescriptionService } from '@/services/prescriptionService'
import { useAuthStore } from '@/stores/auth'

import type { ApiErrorResponse } from '@/types/auth'
import type { Doctor } from '@/types/doctor'
import type { Examination } from '@/types/examination'
import type { Medicine } from '@/types/medicine'
import type {
  AddPrescriptionItemPayload,
  PaginationMeta,
  Prescription,
  PrescriptionItem,
} from '@/types/prescription'

interface PageEvent {
  page: number
  rows: number
}

const auth = useAuthStore()
const toast = useToast()

const prescriptions = ref<Prescription[]>([])
const availableExaminations = ref<Examination[]>([])
const medicines = ref<Medicine[]>([])
const doctors = ref<Doctor[]>([])

const loading = ref(false)
const saving = ref(false)
const itemSaving = ref(false)
const catalogLoading = ref(false)

const submitted = ref(false)
const itemSubmitted = ref(false)

const prescriptionDialog = ref(false)
const detailDialog = ref(false)
const itemDialog = ref(false)
const removeItemDialog = ref(false)
const notesDialog = ref(false)

const selectedPrescription = ref<Prescription | null>(null)
const selectedItem = ref<PrescriptionItem | null>(null)

const errors = ref<Record<string, string[]>>({})
const itemErrors = ref<Record<string, string[]>>({})

const filterDoctorId = ref<number | null>(null)

const meta = ref<PaginationMeta>({
  current_page: 1,
  per_page: 10,
  total: 0,
  last_page: 1,
})

const form = ref({
  examination_id: null as number | null,
  notes: '',
})

const notesForm = ref({
  notes: '',
})

const itemForm = ref({
  medicine_id: null as number | null,
  quantity: 1 as number | null,
  dosage: '',
  usage_instruction: '',
})

onMounted(async () => {
  await loadPrescriptions()

  if (auth.can('DOCTORS.FINDALL')) {
    await loadAllDoctors()
  }
})

function fieldError(field: string) {
  return errors.value[field]?.[0]
}

function itemFieldError(field: string) {
  return itemErrors.value[field]?.[0]
}

function showApiError(error: unknown, fallback: string, targetErrors = errors) {
  let message = fallback

  if (axios.isAxiosError<ApiErrorResponse>(error)) {
    targetErrors.value = error.response?.data.errors ?? {}
    const firstError = Object.values(targetErrors.value)[0]?.[0]
    message = error.response?.data.message ?? firstError ?? fallback
  }

  toast.add({
    severity: 'error',
    summary: 'Error',
    detail: message,
    life: 4000,
  })
}

async function loadPrescriptions(page = 1, perPage = meta.value.per_page) {
  loading.value = true

  try {
    const response = await prescriptionService.getAll({
      page,
      per_page: perPage,
      ...(filterDoctorId.value ? { doctor_id: filterDoctorId.value } : {}),
    })

    prescriptions.value = response.data
    meta.value = response.meta
  } catch (error) {
    showApiError(error, 'Unable to load prescriptions.')
  } finally {
    loading.value = false
  }
}

async function loadAllDoctors() {
  try {
    const result: Doctor[] = []
    let page = 1
    let lastPage = 1

    do {
      const response = await doctorService.getAll(page)
      result.push(...response.data)
      lastPage = response.meta.last_page
      page++
    } while (page <= lastPage)

    doctors.value = result
  } catch (error) {
    showApiError(error, 'Unable to load doctors.')
  }
}

function applyFilter() {
  loadPrescriptions(1, meta.value.per_page)
}

function clearFilter() {
  filterDoctorId.value = null
  loadPrescriptions(1, meta.value.per_page)
}

function doctorLabel(doctor: Doctor) {
  const name = doctor.user?.name ?? 'Unknown doctor'
  const specialty = doctor.specialty?.name
  return specialty ? `${name} — ${specialty}` : name
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

    const existingPrescriptions: Prescription[] = []
    page = 1
    lastPage = 1

    do {
      const response = await prescriptionService.getAll({
        page,
        per_page: 100,
      })

      existingPrescriptions.push(...response.data)
      lastPage = response.meta.last_page
      page++
    } while (page <= lastPage)

    const usedExaminationIds = new Set(
      existingPrescriptions.map((prescription) => prescription.examinations.id),
    )

    availableExaminations.value = examinations.filter(
      (examination) => !usedExaminationIds.has(examination.id),
    )
  } catch (error) {
    showApiError(error, 'Unable to load examinations.')
  } finally {
    catalogLoading.value = false
  }
}

async function loadMedicines() {
  catalogLoading.value = true

  try {
    const result: Medicine[] = []
    let page = 1
    let lastPage = 1

    do {
      const response = await medicineService.getAll({
        page,
        per_page: 100,
        stock_status: 'in_stock',
      })

      result.push(...response.data.filter((medicine) => medicine.is_active))
      lastPage = response.meta.last_page
      page++
    } while (page <= lastPage)

    medicines.value = result
  } catch (error) {
    showApiError(error, 'Unable to load medicines.')
  } finally {
    catalogLoading.value = false
  }
}

const availableMedicinesForPrescription = computed(() => {
  if (!selectedPrescription.value) {
    return medicines.value
  }

  const existingMedicineIds = new Set(
    selectedPrescription.value.items.map((item) => item.medicine.id),
  )

  return medicines.value.filter((medicine) => !existingMedicineIds.has(medicine.id))
})

async function openNew() {
  selectedPrescription.value = null
  submitted.value = false
  errors.value = {}

  form.value = {
    examination_id: null,
    notes: '',
  }

  prescriptionDialog.value = true
  await loadAvailableExaminations()
}

async function savePrescription() {
  submitted.value = true
  errors.value = {}

  if (!form.value.examination_id) {
    return
  }

  saving.value = true

  try {
    const response = await prescriptionService.create({
      examination_id: form.value.examination_id,
      notes: form.value.notes.trim() || null,
    })

    prescriptionDialog.value = false
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Prescription created successfully.',
      life: 3000,
    })

    selectedPrescription.value = response.data
    detailDialog.value = true
    await loadPrescriptions()
  } catch (error) {
    showApiError(error, 'Unable to save prescription.')
  } finally {
    saving.value = false
  }
}

async function openDetails(prescription: Prescription) {
  try {
    const response = await prescriptionService.getOne(prescription.id)
    selectedPrescription.value = response.data
    detailDialog.value = true
  } catch (error) {
    showApiError(error, 'Unable to load prescription.')
  }
}

async function openAddItem() {
  selectedItem.value = null
  itemSubmitted.value = false
  itemErrors.value = {}

  itemForm.value = {
    medicine_id: null,
    quantity: 1,
    dosage: '',
    usage_instruction: '',
  }

  itemDialog.value = true
  await loadMedicines()
}

function openEditItem(item: PrescriptionItem) {
  selectedItem.value = item
  itemSubmitted.value = false
  itemErrors.value = {}

  itemForm.value = {
    medicine_id: item.medicine.id,
    quantity: item.quantity,
    dosage: item.dosage,
    usage_instruction: item.usage_instruction ?? '',
  }

  itemDialog.value = true
}

async function saveItem() {
  if (!selectedPrescription.value) {
    return
  }

  itemSubmitted.value = true
  itemErrors.value = {}

  if (!selectedItem.value && !itemForm.value.medicine_id) {
    toast.add({
      severity: 'error',
      summary: 'Validation Error',
      detail: 'Please select a medicine.',
      life: 3000,
    })
    return
  }

  if (!itemForm.value.quantity || itemForm.value.quantity < 1) {
    toast.add({
      severity: 'error',
      summary: 'Validation Error',
      detail: 'Quantity must be at least 1.',
      life: 3000,
    })
    return
  }

  if (!itemForm.value.dosage.trim()) {
    toast.add({
      severity: 'error',
      summary: 'Validation Error',
      detail: 'Dosage is required.',
      life: 3000,
    })
    return
  }

  itemSaving.value = true

  try {
    if (selectedItem.value) {
      const response = await prescriptionService.updateItem(
        selectedPrescription.value.id,
        selectedItem.value.id,
        {
          quantity: itemForm.value.quantity,
          dosage: itemForm.value.dosage.trim(),
          usage_instruction: itemForm.value.usage_instruction.trim() || undefined,
        },
      )

      selectedPrescription.value = response.data
      toast.add({
        severity: 'success',
        summary: 'Successful',
        detail: response.message,
        life: 3000,
      })
    } else {
      const payload: AddPrescriptionItemPayload = {
        medicine_id: itemForm.value.medicine_id!,
        quantity: itemForm.value.quantity,
        dosage: itemForm.value.dosage.trim(),
        usage_instruction: itemForm.value.usage_instruction.trim() || '',
      }

      const response = await prescriptionService.addItem(selectedPrescription.value.id, payload)
      selectedPrescription.value = response.data
      toast.add({
        severity: 'success',
        summary: 'Successful',
        detail: response.message,
        life: 3000,
      })
    }

    itemDialog.value = false
    await loadPrescriptions(meta.value.current_page)
    await loadMedicines()
  } catch (error) {
    showApiError(error, 'Unable to save item.', itemErrors)
  } finally {
    itemSaving.value = false
  }
}

function confirmRemoveItem(item: PrescriptionItem) {
  selectedItem.value = item
  removeItemDialog.value = true
}

async function removeItem() {
  if (!selectedPrescription.value || !selectedItem.value) {
    return
  }

  itemSaving.value = true

  try {
    const response = await prescriptionService.removeItem(
      selectedPrescription.value.id,
      selectedItem.value.id,
    )

    selectedPrescription.value = response.data
    removeItemDialog.value = false
    toast.add({
      severity: 'success',
      summary: 'Successful',
      detail: response.message,
      life: 3000,
    })

    await loadPrescriptions(meta.value.current_page)
    await loadMedicines()
  } catch (error) {
    showApiError(error, 'Unable to remove medicine.')
  } finally {
    itemSaving.value = false
  }
}

function openNotes() {
  if (!selectedPrescription.value) {
    return
  }

  errors.value = {}
  notesForm.value = {
    notes: selectedPrescription.value.notes ?? '',
  }

  notesDialog.value = true
}

async function saveNotes() {
  if (!selectedPrescription.value) {
    return
  }

  saving.value = true

  try {
    const response = await prescriptionService.update(selectedPrescription.value.id, {
      notes: notesForm.value.notes.trim() || null,
    })

    selectedPrescription.value = response.data
    notesDialog.value = false
    toast.add({
      severity: 'success',
      summary: 'Successful',
      detail: response.message,
      life: 3000,
    })

    await loadPrescriptions(meta.value.current_page)
  } catch (error) {
    showApiError(error, 'Unable to save notes.')
  } finally {
    saving.value = false
  }
}

function onPage(event: PageEvent) {
  loadPrescriptions(event.page + 1, event.rows)
}

function examinationLabel(examination: Examination) {
  return [
    examination.patient_id.code,
    examination.patient_id.full_name,
    formatDateTime(examination.examined_at),
  ].join(' — ')
}

function medicineLabel(medicine: Medicine) {
  return `${medicine.code} — ${medicine.name} (Stock: ${medicine.stock} ${medicine.unit})`
}

function formatDateTime(value: string) {
  const date = new Date(value.replace(' ', 'T'))
  return Number.isNaN(date.getTime()) ? value : date.toLocaleString('vi-VN', { hour12: false })
}
</script>

<template>
  <div>
    <Toast />

    <div class="card">
      <!-- Sakai Toolbar -->
      <Toolbar class="mb-6">
        <template #start>
          <Button
            v-if="auth.can('PRESCRIPTIONS.CREATE')"
            label="New Prescription"
            icon="pi pi-plus"
            severity="secondary"
            @click="openNew"
          />
        </template>
      </Toolbar>

      <!-- Filters -->
      <div v-if="auth.can('DOCTORS.FINDALL')" class="card mb-6 !p-4">
        <div class="flex flex-wrap gap-3 items-end">
          <div class="flex flex-col gap-2">
            <label class="font-medium">Doctor</label>
            <Select
              v-model="filterDoctorId"
              :options="doctors"
              option-value="id"
              filter
              show-clear
              placeholder="All Doctors"
              class="w-64"
            >
              <template #value="slotProps">
                <span v-if="slotProps.value">
                  {{ doctorLabel(doctors.find((d) => d.id === slotProps.value)!) }}
                </span>
                <span v-else>All Doctors</span>
              </template>
              <template #option="slotProps">
                {{ doctorLabel(slotProps.option) }}
              </template>
            </Select>
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

      <!-- Master table -->
      <DataTable
        :value="prescriptions"
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
        current-page-report-template="Showing {first} to {last} of {totalRecords} prescriptions"
        @page="onPage"
      >
        <template #header>
          <div class="flex items-center justify-between flex-wrap gap-2">
            <h4 class="m-0">Manage Prescriptions</h4>
            <span class="text-muted-color">{{ meta.total }} prescriptions</span>
          </div>
        </template>

        <template #empty> No prescriptions found. </template>

        <Column header="Patient" style="min-width: 15rem">
          <template #body="slotProps">
            <div class="flex flex-col">
              <span class="font-medium">
                {{ slotProps.data.examinations?.patient?.full_name ?? '—' }}
              </span>
              <small class="text-muted-color">
                {{ slotProps.data.examinations?.patient?.code ?? '' }}
              </small>
            </div>
          </template>
        </Column>

        <Column header="Doctor" style="min-width: 14rem">
          <template #body="slotProps">
            {{ slotProps.data.doctor?.name ?? '—' }}
          </template>
        </Column>

        <Column header="Medicines" style="min-width: 10rem">
          <template #body="slotProps">
            <Tag :value="`${slotProps.data.items.length} items`" severity="info" />
          </template>
        </Column>

        <Column header="Notes" style="min-width: 18rem">
          <template #body="slotProps">
            {{ slotProps.data.notes ?? '—' }}
          </template>
        </Column>

        <Column header="Created" style="min-width: 13rem">
          <template #body="slotProps">
            {{ formatDateTime(slotProps.data.created_at) }}
          </template>
        </Column>

        <Column :exportable="false" style="min-width: 10rem">
          <template #body="slotProps">
            <Button
              v-if="auth.can('PRESCRIPTIONS.FINDONE')"
              icon="pi pi-eye"
              outlined
              rounded
              severity="info"
              @click="openDetails(slotProps.data)"
            />
          </template>
        </Column>
      </DataTable>
    </div>

    <!-- CREATE PRESCRIPTION -->
    <Dialog
      v-model:visible="prescriptionDialog"
      :style="{ width: '600px' }"
      header="New Prescription"
      modal
    >
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
                  {{ slotProps.option.patient_id.code }} ·
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
            No examinations are available for a new prescription.
          </Message>
        </div>

        <div>
          <label class="block font-bold mb-3"> Notes </label>
          <Textarea v-model="form.notes" rows="4" fluid />
        </div>
      </div>

      <template #footer>
        <Button label="Cancel" icon="pi pi-times" text @click="prescriptionDialog = false" />
        <Button
          label="Create"
          icon="pi pi-check"
          :loading="saving"
          :disabled="availableExaminations.length === 0"
          @click="savePrescription"
        />
      </template>
    </Dialog>

    <!-- MASTER DETAIL -->
    <Dialog
      v-model:visible="detailDialog"
      :style="{ width: '900px' }"
      header="Prescription Details"
      modal
    >
      <div v-if="selectedPrescription" class="flex flex-col gap-5">
        <!-- Avatar Header -->
        <div class="flex items-center gap-4 pb-4 border-b border-surface">
          <div
            class="w-14 h-14 rounded-full bg-primary/10 text-primary flex items-center justify-center text-xl font-bold shrink-0"
          >
            {{
              selectedPrescription.examinations?.patient?.full_name?.charAt(0).toUpperCase() ?? 'P'
            }}
          </div>
          <div class="flex flex-col">
            <span class="text-lg font-semibold">
              {{ selectedPrescription.examinations?.patient?.full_name ?? '—' }}
            </span>
            <span class="text-sm text-muted-color">
              {{ selectedPrescription.examinations?.patient?.code ?? '—' }} · Examination #{{
                selectedPrescription.examinations?.id
              }}
            </span>
            <div class="mt-1">
              <Tag :value="`Prescription #${selectedPrescription.id}`" severity="info" />
            </div>
          </div>
        </div>

        <!-- 2-Col Info Grid -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <span class="text-xs text-muted-color uppercase font-semibold block mb-1">
              Doctor
            </span>
            <span class="font-medium text-sm">
              {{ selectedPrescription.doctor?.name ?? '—' }}
            </span>
          </div>

          <div>
            <span class="text-xs text-muted-color uppercase font-semibold block mb-1">
              Doctor License
            </span>
            <span class="font-medium font-mono text-sm">
              {{ selectedPrescription.doctor?.license_number ?? '—' }}
            </span>
          </div>

          <div>
            <span class="text-xs text-muted-color uppercase font-semibold block mb-1">
              Examination ID
            </span>
            <span class="font-medium font-mono text-sm">
              #{{ selectedPrescription.examinations?.id }}
            </span>
          </div>

          <div>
            <span class="text-xs text-muted-color uppercase font-semibold block mb-1">
              Created At
            </span>
            <span class="text-sm text-muted-color">
              {{ formatDateTime(selectedPrescription.created_at) }}
            </span>
          </div>
        </div>

        <!-- Notes -->
        <div>
          <div class="flex items-center justify-between mb-1">
            <span class="text-xs text-muted-color uppercase font-semibold">Notes</span>
            <Button
              v-if="auth.can('PRESCRIPTIONS.UPDATE')"
              icon="pi pi-pencil"
              label="Edit Notes"
              text
              size="small"
              @click="openNotes"
            />
          </div>
          <div
            class="p-3 bg-surface-50 dark:bg-surface-800 rounded-lg text-sm leading-relaxed text-surface-700 dark:text-surface-200 whitespace-pre-line"
          >
            {{ selectedPrescription.notes || 'No notes provided.' }}
          </div>
        </div>

        <!-- Items toolbar -->
        <Toolbar>
          <template #start>
            <div class="font-semibold text-base">Prescribed Medicines</div>
          </template>

          <template #end>
            <Button
              v-if="auth.can('PRESCRIPTIONS.ADDITEM')"
              label="Add Medicine"
              icon="pi pi-plus"
              size="small"
              @click="openAddItem"
            />
          </template>
        </Toolbar>

        <!-- Detail DataTable -->
        <DataTable :value="selectedPrescription.items" data-key="id" striped-rows>
          <template #empty> No medicines added yet. </template>

          <Column header="Medicine" style="min-width: 15rem">
            <template #body="slotProps">
              <div class="flex flex-col">
                <span class="font-medium">
                  {{ slotProps.data.medicine?.name }}
                </span>
                <small class="text-muted-color">
                  {{ slotProps.data.medicine?.code }}
                </small>
              </div>
            </template>
          </Column>

          <Column header="Quantity" style="width: 9rem">
            <template #body="slotProps">
              {{ slotProps.data.quantity }}
              {{ slotProps.data.medicine?.unit }}
            </template>
          </Column>

          <Column field="dosage" header="Dosage" style="min-width: 12rem" />

          <Column header="Instructions" style="min-width: 16rem">
            <template #body="slotProps">
              {{ slotProps.data.usage_instruction ?? '—' }}
            </template>
          </Column>

          <Column :exportable="false" style="width: 10rem">
            <template #body="slotProps">
              <Button
                v-if="auth.can('PRESCRIPTIONS.UPDATEITEM')"
                icon="pi pi-pencil"
                outlined
                rounded
                class="mr-2"
                @click="openEditItem(slotProps.data)"
              />

              <Button
                v-if="auth.can('PRESCRIPTIONS.REMOVEITEM')"
                icon="pi pi-trash"
                outlined
                rounded
                severity="danger"
                @click="confirmRemoveItem(slotProps.data)"
              />
            </template>
          </Column>
        </DataTable>
      </div>

      <template #footer>
        <Button label="Close" icon="pi pi-times" text @click="detailDialog = false" />
      </template>
    </Dialog>

    <!-- ADD / EDIT ITEM -->
    <Dialog
      v-model:visible="itemDialog"
      :style="{ width: '550px' }"
      :header="selectedItem ? 'Edit Medicine' : 'Add Medicine'"
      modal
    >
      <div class="flex flex-col gap-6">
        <div>
          <label class="block font-bold mb-3"> Medicine </label>

          <!-- Medicine cannot change on edit -->
          <Select
            v-if="!selectedItem"
            v-model="itemForm.medicine_id"
            :options="availableMedicinesForPrescription"
            option-value="id"
            filter
            fluid
            :loading="catalogLoading"
            placeholder="Select medicine"
            :invalid="(itemSubmitted && !itemForm.medicine_id) || !!itemFieldError('medicine_id')"
          >
            <template #value="slotProps">
              <span v-if="slotProps.value">
                {{ medicineLabel(medicines.find((medicine) => medicine.id === slotProps.value)!) }}
              </span>
              <span v-else> Select medicine </span>
            </template>

            <template #option="slotProps">
              <div class="flex flex-col">
                <span class="font-medium">
                  {{ slotProps.option.name }}
                </span>
                <small class="text-muted-color">
                  {{ slotProps.option.code }} · Stock:
                  {{ slotProps.option.stock }}
                  {{ slotProps.option.unit }}
                </small>
              </div>
            </template>
          </Select>

          <InputText
            v-else
            :model-value="`${selectedItem.medicine.code} — ${selectedItem.medicine.name}`"
            disabled
            fluid
          />

          <small v-if="itemFieldError('medicine_id')" class="text-red-500">
            {{ itemFieldError('medicine_id') }}
          </small>
          <small
            v-else-if="itemSubmitted && !itemForm.medicine_id && !selectedItem"
            class="text-red-500"
          >
            Medicine is required.
          </small>
        </div>

        <div>
          <label class="block font-bold mb-3"> Quantity </label>
          <InputNumber
            v-model="itemForm.quantity"
            :min="1"
            :use-grouping="false"
            show-buttons
            fluid
            :invalid="
              (itemSubmitted && (!itemForm.quantity || itemForm.quantity < 1)) ||
              !!itemFieldError('quantity')
            "
          />

          <small v-if="itemFieldError('quantity')" class="text-red-500">
            {{ itemFieldError('quantity') }}
          </small>
          <small
            v-else-if="itemSubmitted && (!itemForm.quantity || itemForm.quantity < 1)"
            class="text-red-500"
          >
            Quantity must be at least 1.
          </small>
        </div>

        <div>
          <label class="block font-bold mb-3"> Dosage </label>
          <InputText
            v-model.trim="itemForm.dosage"
            placeholder="e.g. 500mg twice daily"
            fluid
            :invalid="(itemSubmitted && !itemForm.dosage.trim()) || !!itemFieldError('dosage')"
          />

          <small v-if="itemFieldError('dosage')" class="text-red-500">
            {{ itemFieldError('dosage') }}
          </small>
          <small v-else-if="itemSubmitted && !itemForm.dosage.trim()" class="text-red-500">
            Dosage is required.
          </small>
        </div>

        <div>
          <label class="block font-bold mb-3"> Usage Instruction </label>
          <Textarea
            v-model="itemForm.usage_instruction"
            rows="3"
            fluid
            placeholder="e.g. After meals"
            :invalid="!!itemFieldError('usage_instruction')"
          />

          <small v-if="itemFieldError('usage_instruction')" class="text-red-500">
            {{ itemFieldError('usage_instruction') }}
          </small>
        </div>
      </div>

      <template #footer>
        <Button label="Cancel" icon="pi pi-times" text @click="itemDialog = false" />
        <Button label="Save" icon="pi pi-check" :loading="itemSaving" @click="saveItem" />
      </template>
    </Dialog>

    <!-- EDIT NOTES -->
    <Dialog v-model:visible="notesDialog" :style="{ width: '500px' }" header="Edit Notes" modal>
      <Textarea v-model="notesForm.notes" rows="6" fluid />

      <template #footer>
        <Button label="Cancel" icon="pi pi-times" text @click="notesDialog = false" />
        <Button label="Save" icon="pi pi-check" :loading="saving" @click="saveNotes" />
      </template>
    </Dialog>

    <!-- REMOVE ITEM -->
    <Dialog v-model:visible="removeItemDialog" :style="{ width: '450px' }" header="Confirm" modal>
      <div class="flex items-center gap-4">
        <i class="pi pi-exclamation-triangle !text-3xl text-red-500" />
        <span v-if="selectedItem">
          Remove <b>{{ selectedItem.medicine.name }}</b> from this prescription?
        </span>
      </div>

      <Message severity="info" class="mt-4">
        Removed quantity will be returned to medicine stock.
      </Message>

      <template #footer>
        <Button label="No" icon="pi pi-times" text @click="removeItemDialog = false" />
        <Button
          label="Yes"
          icon="pi pi-check"
          severity="danger"
          :loading="itemSaving"
          @click="removeItem"
        />
      </template>
    </Dialog>
  </div>
</template>
