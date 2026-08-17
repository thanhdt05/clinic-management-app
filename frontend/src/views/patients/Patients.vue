<script setup lang="ts">
import axios from 'axios'
import { useToast } from 'primevue/usetoast'
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'

import { patientService } from '@/services/patientService'
import { useAuthStore } from '@/stores/auth'

import type { ApiErrorResponse } from '@/types/auth'
import type { PaginationMeta, Patient, PatientGender, PatientPayload } from '@/types/patient'

interface PageEvent {
  page: number
  rows: number
}

const auth = useAuthStore()
const toast = useToast()

const patients = ref<Patient[]>([])

const loading = ref(false)
const detailLoading = ref(false)
const saving = ref(false)
const submitted = ref(false)

const patientDialog = ref(false)
const patientDetailDialog = ref(false)
const deletePatientDialog = ref(false)

const selectedPatient = ref<Patient | null>(null)
const detailPatient = ref<Patient | null>(null)

const errors = ref<Record<string, string[]>>({})

const search = ref('')

const form = ref({
  full_name: '',
  gender: null as PatientGender | null,
  date_of_birth: null as Date | null,
  phone: '',
  email: '',
  address: '',
})

const meta = ref<PaginationMeta>({
  current_page: 1,
  per_page: 10,
  total: 0,
  last_page: 1,
})

const genderOptions = [
  {
    label: 'Male',
    value: 'male',
  },
  {
    label: 'Female',
    value: 'female',
  },
  {
    label: 'Other',
    value: 'other',
  },
]

const today = new Date()

let searchTimer: ReturnType<typeof setTimeout> | null = null

onMounted(() => {
  loadPatients()
})

onBeforeUnmount(() => {
  if (searchTimer) {
    clearTimeout(searchTimer)
  }
})

watch(search, () => {
  if (searchTimer) {
    clearTimeout(searchTimer)
  }

  searchTimer = setTimeout(() => {
    loadPatients(1, meta.value.per_page)
  }, 400)
})

function fieldError(field: string) {
  return errors.value[field]?.[0]
}

function genderLabel(gender: PatientGender) {
  return genderOptions.find((item) => item.value === gender)?.label ?? gender
}

function genderAvatarClass(gender: PatientGender | undefined | null) {
  if (gender === 'male') {
    return 'bg-blue-100 text-blue-600 dark:bg-blue-950 dark:text-blue-400'
  }
  if (gender === 'female') {
    return 'bg-pink-100 text-pink-600 dark:bg-pink-950 dark:text-pink-400'
  }
  return 'bg-purple-100 text-purple-600 dark:bg-purple-950 dark:text-purple-400'
}

function genderTagClass(gender: PatientGender | undefined | null) {
  if (gender === 'male') {
    return '!bg-blue-50 !text-blue-700 dark:!bg-blue-950/60 dark:!text-blue-300 !border !border-blue-200 dark:!border-blue-800'
  }
  if (gender === 'female') {
    return '!bg-pink-50 !text-pink-700 dark:!bg-pink-950/60 dark:!text-pink-300 !border !border-pink-200 dark:!border-pink-800'
  }
  return '!bg-purple-50 !text-purple-700 dark:!bg-purple-950/60 dark:!text-purple-300 !border !border-purple-200 dark:!border-purple-800'
}

function parseDate(value: string | null | undefined): Date | null {
  if (!value) {
    return null
  }

  const [year, month, day] = value.split('-').map(Number)

  if (!year || !month || !day) {
    return null
  }

  return new Date(year, month - 1, day)
}

function formatDate(date: Date): string {
  const year = date.getFullYear()

  const month = String(date.getMonth() + 1).padStart(2, '0')

  const day = String(date.getDate()).padStart(2, '0')

  return `${year}-${month}-${day}`
}

function handleValidationError(error: unknown) {
  if (axios.isAxiosError<ApiErrorResponse>(error)) {
    errors.value = error.response?.data.errors ?? {}

    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data.message || 'Validation failed.',
      life: 4000,
    })

    return
  }

  showApiError(error, 'Unable to save patient.')
}

function showApiError(error: unknown, fallback: string) {
  let message = fallback

  if (axios.isAxiosError<ApiErrorResponse>(error)) {
    const apiErrors = error.response?.data?.errors
    const firstError = apiErrors ? Object.values(apiErrors)[0]?.[0] : null

    message = firstError || error.response?.data?.message || fallback
  }

  toast.add({
    severity: 'error',
    summary: 'Error',
    detail: message,
    life: 4000,
  })
}

async function loadPatients(page = meta.value.current_page, perPage = meta.value.per_page) {
  loading.value = true

  try {
    const keyword = search.value.trim()
    const response = await patientService.getAll({
      page,
      per_page: perPage,

      ...(keyword ? { q: keyword } : {}),
    })

    patients.value = response.data
    meta.value = response.meta
  } catch (error) {
    showApiError(error, 'Unable to load patients.')
  } finally {
    loading.value = false
  }
}

function openNew() {
  selectedPatient.value = null
  submitted.value = false
  errors.value = {}

  form.value = {
    full_name: '',
    gender: null,
    date_of_birth: null,
    phone: '',
    email: '',
    address: '',
  }

  patientDialog.value = true
}

function hideDialog() {
  patientDialog.value = false
  submitted.value = false
  errors.value = {}
}

async function viewPatient(patient: Patient) {
  patientDetailDialog.value = true
  detailPatient.value = null
  detailLoading.value = true

  try {
    const response = await patientService.getOne(patient.id)

    detailPatient.value = response.data
  } catch (error) {
    patientDetailDialog.value = false
    showApiError(error, 'Unable to load patient details.')
  } finally {
    detailLoading.value = false
  }
}

async function savePatient() {
  submitted.value = true
  errors.value = {}

  if (
    !form.value.full_name.trim() ||
    !form.value.gender ||
    !form.value.date_of_birth ||
    !form.value.phone.trim()
  ) {
    return
  }

  saving.value = true

  const payload: PatientPayload = {
    full_name: form.value.full_name.trim(),
    gender: form.value.gender,
    date_of_birth: formatDate(form.value.date_of_birth),
    phone: form.value.phone.trim(),
    email: form.value.email.trim() || null,
    address: form.value.address.trim() || null,
  }

  try {
    if (selectedPatient.value) {
      const response = await patientService.update(selectedPatient.value.id, payload)

      toast.add({
        severity: 'success',
        summary: 'Successful',
        detail: response.message,
        life: 3000,
      })
    } else {
      const response = await patientService.create(payload)

      toast.add({
        severity: 'success',
        summary: 'Successful',
        detail: response.message,
        life: 3000,
      })
    }

    patientDialog.value = false
    await loadPatients(selectedPatient.value ? meta.value.current_page : 1)
  } catch (error) {
    handleValidationError(error)
  } finally {
    saving.value = false
  }
}

function editPatient(patient: Patient) {
  selectedPatient.value = patient
  submitted.value = false
  errors.value = {}

  form.value = {
    full_name: patient.full_name,
    gender: patient.gender,
    date_of_birth: parseDate(patient.date_of_birth),
    phone: patient.phone,
    email: patient.email ?? '',
    address: patient.address ?? '',
  }

  patientDialog.value = true
}

function openEditFromView(patient: Patient) {
  patientDetailDialog.value = false
  editPatient(patient)
}

function confirmDeleteSelected(patient: Patient) {
  selectedPatient.value = patient
  deletePatientDialog.value = true
}

async function deletePatient() {
  if (!selectedPatient.value) {
    return
  }

  try {
    const response = await patientService.remove(selectedPatient.value.id)

    deletePatientDialog.value = false

    toast.add({
      severity: 'success',
      summary: 'Successful',
      detail: response.message,
      life: 3000,
    })

    const nextPage =
      patients.value.length === 1 && meta.value.current_page > 1
        ? meta.value.current_page - 1
        : meta.value.current_page

    await loadPatients(nextPage)
  } catch (error) {
    showApiError(error, 'Unable to delete patient.')
  }
}

function onPage(event: PageEvent) {
  loadPatients(event.page + 1, event.rows)
}
</script>

<template>
  <div>
    <Toast />
    <div class="card">
      <Toolbar class="mb-6">
        <template #start>
          <Button
            v-if="auth.can('PATIENTS.CREATE')"
            label="New"
            icon="pi pi-plus"
            severity="secondary"
            class="mr-2"
            @click="openNew"
          />
        </template>
      </Toolbar>

      <DataTable
        :value="patients"
        dataKey="id"
        lazy
        paginator
        :rows="meta.per_page"
        :first="(meta.current_page - 1) * meta.per_page"
        :total-records="meta.total"
        :loading="loading"
        paginator-template="
          FirstPageLink
          PrevPageLink
          PageLinks
          NextPageLink
          LastPageLink
          CurrentPageReport
        "
        current-page-report-template="
          Showing {first} to {last}
          of {totalRecords} patients
        "
        @page="onPage"
      >
        <template #header>
          <div class="flex flex-wrap gap-2 items-center justify-between">
            <div class="flex items-center gap-2">
              <h4 class="m-0">Manage Patients</h4>
              <span class="text-muted-color text-sm font-normal">({{ meta.total }} patients)</span>
            </div>
            <IconField>
              <InputIcon>
                <i class="pi pi-search" />
              </InputIcon>
              <InputText v-model="search" placeholder="Search patients..." />
            </IconField>
          </div>
        </template>

        <template #empty> No patients found. </template>

        <Column field="code" header="Code" style="min-width: 10rem" />

        <Column field="full_name" header="Full Name" style="min-width: 15rem" />

        <Column header="Gender" style="min-width: 8rem">
          <template #body="slotProps">
            <Tag
              :value="genderLabel(slotProps.data.gender)"
              :class="genderTagClass(slotProps.data.gender)"
            />
          </template>
        </Column>

        <Column field="date_of_birth" header="Date of Birth" style="min-width: 11rem" />

        <Column field="phone" header="Phone" style="min-width: 11rem" />

        <Column field="email" header="Email" style="min-width: 14rem">
          <template #body="slotProps">
            {{ slotProps.data.email ?? '—' }}
          </template>
        </Column>

        <Column :exportable="false" style="min-width: 13rem">
          <template #body="slotProps">
            <Button
              v-if="auth.can('PATIENTS.FINDONE')"
              icon="pi pi-eye"
              outlined
              rounded
              class="mr-2"
              severity="info"
              @click="viewPatient(slotProps.data)"
            />
            <Button
              v-if="auth.can('PATIENTS.UPDATE')"
              icon="pi pi-pencil"
              outlined
              rounded
              class="mr-2"
              @click="editPatient(slotProps.data)"
            />
            <Button
              v-if="auth.can('PATIENTS.DELETE')"
              icon="pi pi-trash"
              outlined
              rounded
              severity="danger"
              @click="confirmDeleteSelected(slotProps.data)"
            />
          </template>
        </Column>
      </DataTable>
    </div>

    <Dialog
      v-model:visible="patientDialog"
      :style="{ width: '650px' }"
      :header="selectedPatient ? 'Edit Patient' : 'Patient Details'"
      modal
    >
      <div class="grid grid-cols-12 gap-4">
        <div class="col-span-12 md:col-span-8">
          <label for="full_name" class="block font-bold mb-3">Full Name</label>
          <InputText
            id="full_name"
            v-model.trim="form.full_name"
            autofocus
            :invalid="(submitted && !form.full_name) || !!fieldError('full_name')"
            fluid
          />
          <small v-if="fieldError('full_name')" class="text-red-500">{{
            fieldError('full_name')
          }}</small>
          <small v-else-if="submitted && !form.full_name" class="text-red-500">
            Full name is required.
          </small>
        </div>

        <div class="col-span-12 md:col-span-4">
          <label for="gender" class="block font-bold mb-3"> Gender </label>

          <Select
            id="gender"
            v-model="form.gender"
            :options="genderOptions"
            option-label="label"
            option-value="value"
            placeholder="
              Select Gender
            "
            fluid
            :invalid="(submitted && !form.gender) || !!fieldError('gender')"
          />

          <small v-if="fieldError('gender')" class="text-red-500">
            {{ fieldError('gender') }}
          </small>
        </div>

        <div class="col-span-12 md:col-span-6">
          <label for="birth-date" class="block font-bold mb-3"> Date of Birth </label>

          <DatePicker
            id="birth-date"
            v-model="form.date_of_birth"
            date-format="yy-mm-dd"
            :max-date="today"
            :manual-input="false"
            show-icon
            fluid
            :invalid="(submitted && !form.date_of_birth) || !!fieldError('date_of_birth')"
          />

          <small v-if="fieldError('date_of_birth')" class="text-red-500">
            {{ fieldError('date_of_birth') }}
          </small>
        </div>

        <div class="col-span-12 md:col-span-6">
          <label for="phone" class="block font-bold mb-3"> Phone </label>

          <InputText
            id="phone"
            v-model.trim="form.phone"
            fluid
            :invalid="(submitted && !form.phone) || !!fieldError('phone')"
          />

          <small v-if="fieldError('phone')" class="text-red-500">
            {{ fieldError('phone') }}
          </small>
        </div>

        <div class="col-span-12">
          <label for="email" class="block font-bold mb-3"> Email </label>

          <InputText
            id="email"
            v-model.trim="form.email"
            type="email"
            fluid
            :invalid="!!fieldError('email')"
          />

          <small v-if="fieldError('email')" class="text-red-500">
            {{ fieldError('email') }}
          </small>
        </div>

        <div class="col-span-12">
          <label for="address" class="block font-bold mb-3"> Address </label>

          <Textarea
            id="address"
            v-model="form.address"
            rows="3"
            fluid
            :invalid="!!fieldError('address')"
          />

          <small v-if="fieldError('address')" class="text-red-500">
            {{ fieldError('address') }}
          </small>
        </div>
      </div>

      <template #footer>
        <Button label="Cancel" icon="pi pi-times" text @click="hideDialog" />
        <Button label="Save" icon="pi pi-check" :loading="saving" @click="savePatient" />
      </template>
    </Dialog>

    <!-- View Dialog -->
    <Dialog
      v-model:visible="patientDetailDialog"
      :style="{
        width: '550px',
      }"
      header="Patient Details"
      modal
    >
      <div v-if="detailLoading" class="flex justify-center py-8">
        <ProgressSpinner />
      </div>

      <div v-else-if="detailPatient" class="flex flex-col gap-5">
        <div class="flex items-center gap-4 pb-4 border-b border-surface">
          <div
            class="w-14 h-14 rounded-full flex items-center justify-center text-xl font-bold shrink-0"
            :class="genderAvatarClass(detailPatient.gender)"
          >
            {{ detailPatient.full_name?.charAt(0).toUpperCase() ?? 'P' }}
          </div>
          <div class="flex flex-col">
            <span class="text-lg font-semibold">{{ detailPatient.full_name }}</span>
            <span class="text-sm text-muted-color">{{ detailPatient.email ?? '—' }}</span>
            <div class="mt-1 flex items-center gap-2">
              <Tag
                :value="genderLabel(detailPatient.gender)"
                :class="genderTagClass(detailPatient.gender)"
              />
              <Tag :value="detailPatient.code" severity="secondary" />
            </div>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <span class="text-xs text-muted-color uppercase font-semibold block mb-1"
              >Date of Birth</span
            >
            <span class="font-medium text-sm">{{ detailPatient.date_of_birth ?? '—' }}</span>
          </div>

          <div>
            <span class="text-xs text-muted-color uppercase font-semibold block mb-1"
              >Phone Number</span
            >
            <span class="font-medium font-mono text-sm">{{ detailPatient.phone ?? '—' }}</span>
          </div>

          <div>
            <span class="text-xs text-muted-color uppercase font-semibold block mb-1"
              >Created At</span
            >
            <span class="text-sm text-muted-color">{{ detailPatient.created_at ?? '—' }}</span>
          </div>

          <div>
            <span class="text-xs text-muted-color uppercase font-semibold block mb-1"
              >Updated At</span
            >
            <span class="text-sm text-muted-color">{{ detailPatient.updated_at ?? '—' }}</span>
          </div>
        </div>

        <div>
          <span class="text-xs text-muted-color uppercase font-semibold block mb-1">Address</span>
          <div
            class="p-3 bg-surface-50 dark:bg-surface-800 rounded-lg text-sm leading-relaxed text-surface-700 dark:text-surface-200"
          >
            {{ detailPatient.address || 'No address provided.' }}
          </div>
        </div>
      </div>

      <template #footer>
        <Button label="Close" icon="pi pi-times" text @click="patientDetailDialog = false" />
        <Button
          v-if="auth.can('PATIENTS.UPDATE') && detailPatient"
          label="Edit"
          icon="pi pi-pencil"
          @click="openEditFromView(detailPatient)"
        />
      </template>
    </Dialog>

    <Dialog
      v-model:visible="deletePatientDialog"
      :style="{ width: '450px' }"
      header="Confirm"
      modal
    >
      <div class="flex items-center gap-4">
        <i class="pi pi-exclamation-triangle text-3xl!" />
        <span v-if="selectedPatient"
          >Are you sure you want to delete <b>{{ selectedPatient.full_name }}</b
          >?</span
        >
      </div>
      <template #footer>
        <Button label="No" icon="pi pi-times" text @click="deletePatientDialog = false" />
        <Button label="Yes" icon="pi pi-check" severity="danger" @click="deletePatient" />
      </template>
    </Dialog>
  </div>
</template>
