<script setup lang="ts">
import axios from 'axios'
import { useToast } from 'primevue/usetoast'
import { onMounted, ref } from 'vue'

import { appointmentService } from '@/services/appointmentService'
import { doctorService } from '@/services/doctorService'
import { examinationService } from '@/services/examinationService'
import { patientService } from '@/services/patientService'
import { useAuthStore } from '@/stores/auth'

import type { ApiErrorResponse } from '@/types/auth'
import type { Appointment } from '@/types/appointment'
import type { Doctor } from '@/types/doctor'
import type { Patient } from '@/types/patient'
import type {
  CreateExaminationPayload,
  Examination,
  PaginationMeta,
  UpdateExaminationPayload,
} from '@/types/examination'

interface PageEvent {
  page: number
  rows: number
}

const auth = useAuthStore()
const toast = useToast()

const examinations = ref<Examination[]>([])
const confirmedAppointments = ref<Appointment[]>([])
const doctors = ref<Doctor[]>([])
const patients = ref<Patient[]>([])

const loading = ref(false)
const appointmentLoading = ref(false)
const saving = ref(false)
const detailLoading = ref(false)
const submitted = ref(false)

const examinationDialog = ref(false)
const detailDialog = ref(false)

const selectedExamination = ref<Examination | null>(null)
const detailExamination = ref<Examination | null>(null)

const errors = ref<Record<string, string[]>>({})

const filterDoctorId = ref<number | null>(null)
const filterPatientId = ref<number | null>(null)

const form = ref({
  appointment_id: null as number | null,
  diagnosis: '',
  notes: '',
})

const meta = ref<PaginationMeta>({
  current_page: 1,
  per_page: 10,
  total: 0,
  last_page: 1,
})

onMounted(async () => {
  await loadExaminations()

  if (auth.can('PATIENTS.FINDALL')) {
    await loadAllPatients()
  }

  if (auth.can('DOCTORS.FINDALL')) {
    await loadAllDoctors()
  }
})

function fieldError(field: string) {
  return errors.value[field]?.[0]
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

async function loadExaminations(page = 1, perPage = meta.value.per_page) {
  loading.value = true

  try {
    const response = await examinationService.getAll({
      page,
      per_page: perPage,
      ...(filterDoctorId.value ? { doctor_id: filterDoctorId.value } : {}),
      ...(filterPatientId.value ? { patient_id: filterPatientId.value } : {}),
    })

    examinations.value = response.data
    meta.value = response.meta
  } catch (error) {
    showApiError(error, 'Unable to load examinations.')
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

async function loadAllPatients() {
  try {
    const result: Patient[] = []
    let page = 1
    let lastPage = 1

    do {
      const response = await patientService.getAll({ page, per_page: 100 })
      result.push(...response.data)
      lastPage = response.meta.last_page
      page++
    } while (page <= lastPage)

    patients.value = result
  } catch (error) {
    showApiError(error, 'Unable to load patients.')
  }
}

function applyFilters() {
  loadExaminations(1, meta.value.per_page)
}

function clearFilters() {
  filterDoctorId.value = null
  filterPatientId.value = null
  loadExaminations(1, meta.value.per_page)
}

function doctorLabel(doctor: Doctor) {
  const name = doctor.user?.name ?? 'Unknown doctor'
  const specialty = doctor.specialty?.name
  return specialty ? `${name} — ${specialty}` : name
}

function patientLabel(patient: Patient) {
  return `${patient.full_name} (${patient.code})`
}

async function loadConfirmedAppointments() {
  appointmentLoading.value = true

  try {
    const result: Appointment[] = []
    let page = 1
    let lastPage = 1

    do {
      const response = await appointmentService.getAll({
        page,
        per_page: 100,
        status: 'confirmed',
      })

      result.push(...response.data)
      lastPage = response.meta.last_page
      page++
    } while (page <= lastPage)

    confirmedAppointments.value = result
  } catch (error) {
    showApiError(error, 'Unable to load confirmed appointments.')
  } finally {
    appointmentLoading.value = false
  }
}

async function openNew() {
  selectedExamination.value = null
  submitted.value = false
  errors.value = {}

  form.value = {
    appointment_id: null,
    diagnosis: '',
    notes: '',
  }

  examinationDialog.value = true

  await loadConfirmedAppointments()
}

function hideDialog() {
  examinationDialog.value = false
  submitted.value = false
  errors.value = {}
}

async function saveExamination() {
  submitted.value = true
  errors.value = {}

  if (!form.value.diagnosis.trim()) {
    return
  }

  if (!selectedExamination.value && !form.value.appointment_id) {
    return
  }

  saving.value = true

  try {
    if (selectedExamination.value) {
      const payload: UpdateExaminationPayload = {
        diagnosis: form.value.diagnosis.trim(),
        notes: form.value.notes.trim(),
      }
      const response = await examinationService.update(selectedExamination.value.id, payload)
      toast.add({
        severity: 'success',
        summary: 'Successful',
        detail: response.message,
        life: 3000,
      })
    } else {
      const payload: CreateExaminationPayload = {
        appointment_id: form.value.appointment_id!,
        diagnosis: form.value.diagnosis.trim(),
        notes: form.value.notes,
      }

      const response = await examinationService.create(payload)
      toast.add({
        severity: 'success',
        summary: 'Successful',
        detail: response.message,
        life: 3000,
      })
    }

    examinationDialog.value = false
    await loadExaminations(selectedExamination.value ? meta.value.current_page : 1)
  } catch (error) {
    showApiError(error, 'Unable to save examination.')
  } finally {
    saving.value = false
  }
}

function editExamination(examination: Examination) {
  selectedExamination.value = examination
  submitted.value = false
  errors.value = {}

  form.value = {
    appointment_id: examination.appointment_id,
    diagnosis: examination.diagnosis,
    notes: examination.notes ?? '',
  }

  examinationDialog.value = true
}

async function viewExamination(examination: Examination) {
  detailDialog.value = true
  detailExamination.value = null
  detailLoading.value = true

  try {
    const response = await examinationService.getOne(examination.id)
    detailExamination.value = response.data
  } catch (error) {
    detailDialog.value = false
    showApiError(error, 'Unable to load examination details.')
  } finally {
    detailLoading.value = false
  }
}

function openEditFromView(examination: Examination) {
  editExamination(examination)
  detailDialog.value = false
}

function onPage(event: PageEvent) {
  loadExaminations(event.page + 1, event.rows)
}

function formatDateTime(value: string) {
  const date = new Date(value.replace(' ', 'T'))
  return Number.isNaN(date.getTime()) ? value : date.toLocaleString('vi-VN', { hour12: false })
}

function truncate(value: string, length = 70) {
  if (value.length <= length) {
    return value
  }

  return `${value.slice(0, length)}...`
}

function appointmentLabel(appointment: Appointment) {
  const patient = appointment.patient
  const doctor = appointment.doctor

  return [
    patient ? `${patient.code} — ${patient.full_name}` : 'Unknown patient',
    doctor ? doctor.name : 'Unknown doctor',
    formatDateTime(appointment.scheduled_at),
  ].join(' · ')
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
            v-if="auth.can('EXAMINATIONS.CREATE')"
            label="New Examination"
            icon="pi pi-plus"
            severity="secondary"
            @click="openNew"
          />
        </template>
      </Toolbar>

      <!-- Filters -->
      <div class="card mb-6 !p-4">
        <div class="flex flex-wrap gap-3 items-end">
          <div v-if="auth.can('PATIENTS.FINDALL')" class="flex flex-col gap-2">
            <label class="font-medium">Patient</label>
            <Select
              v-model="filterPatientId"
              :options="patients"
              option-value="id"
              filter
              show-clear
              placeholder="All Patients"
              class="w-64"
            >
              <template #value="slotProps">
                <span v-if="slotProps.value">
                  {{ patientLabel(patients.find((p) => p.id === slotProps.value)!) }}
                </span>
                <span v-else>All Patients</span>
              </template>
              <template #option="slotProps">
                {{ patientLabel(slotProps.option) }}
              </template>
            </Select>
          </div>

          <div v-if="auth.can('DOCTORS.FINDALL')" class="flex flex-col gap-2">
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

          <Button label="Filter" icon="pi pi-filter" @click="applyFilters" />
          <Button
            label="Clear"
            icon="pi pi-filter-slash"
            severity="secondary"
            text
            @click="clearFilters"
          />
        </div>
      </div>

      <!-- Sakai DataTable -->
      <DataTable
        :value="examinations"
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
        current-page-report-template="Showing {first} to {last} of {totalRecords} examinations"
        @page="onPage"
      >
        <template #header>
          <div class="flex flex-wrap gap-2 items-center justify-between">
            <h4 class="m-0">Manage Examinations</h4>
            <span class="text-muted-color">{{ meta.total }} examinations</span>
          </div>
        </template>

        <template #empty> No examinations found. </template>

        <Column header="Patient" style="min-width: 14rem">
          <template #body="slotProps">
            <div class="flex flex-col">
              <span class="font-medium">{{ slotProps.data.patient_id?.full_name ?? '—' }}</span>
              <small class="text-muted-color">{{ slotProps.data.patient_id?.code ?? '' }}</small>
            </div>
          </template>
        </Column>

        <Column header="Doctor" style="min-width: 14rem">
          <template #body="slotProps">
            <div class="flex flex-col">
              <span class="font-medium">{{ slotProps.data.doctor_id?.name ?? '—' }}</span>
              <small class="text-muted-color"
                >License: {{ slotProps.data.doctor_id?.license_number ?? '—' }}</small
              >
            </div>
          </template>
        </Column>

        <Column header="Diagnosis" style="min-width: 20rem">
          <template #body="slotProps">
            {{ truncate(slotProps.data.diagnosis) }}
          </template>
        </Column>

        <Column header="Examined At" style="min-width: 13rem">
          <template #body="slotProps">
            {{ formatDateTime(slotProps.data.examined_at) }}
          </template>
        </Column>

        <Column :exportable="false" style="min-width: 10rem">
          <template #body="slotProps">
            <Button
              v-if="auth.can('EXAMINATIONS.FINDONE')"
              icon="pi pi-eye"
              outlined
              rounded
              severity="info"
              class="mr-2"
              @click="viewExamination(slotProps.data)"
            />

            <Button
              v-if="auth.can('EXAMINATIONS.UPDATE')"
              icon="pi pi-pencil"
              outlined
              rounded
              @click="editExamination(slotProps.data)"
            />
          </template>
        </Column>
      </DataTable>
    </div>

    <!-- Create/Edit Examination -->
    <Dialog
      v-model:visible="examinationDialog"
      :style="{ width: '650px' }"
      :header="selectedExamination ? 'Edit Examination' : 'Examination Details'"
      modal
    >
      <div class="flex flex-col gap-6">
        <!-- Appointment CREATE -->
        <div v-if="!selectedExamination">
          <label class="block font-bold mb-3"> Confirmed Appointment </label>
          <Select
            v-model="form.appointment_id"
            :options="confirmedAppointments"
            option-value="id"
            filter
            fluid
            :loading="appointmentLoading"
            placeholder="Select appointment"
            :invalid="(submitted && !form.appointment_id) || !!fieldError('appointment_id')"
          >
            <template #value="slotProps">
              <span v-if="slotProps.value">
                {{
                  appointmentLabel(
                    confirmedAppointments.find((item) => item.id === slotProps.value)!,
                  )
                }}
              </span>
              <span v-else> Select confirmed appointment </span>
            </template>

            <template #option="slotProps">
              <div class="flex flex-col">
                <span class="font-medium">{{ slotProps.option.patient?.full_name }}</span>
                <small class="text-muted-color">
                  {{ slotProps.option.patient?.code }} · {{ slotProps.option.doctor?.name }} ·
                  {{ formatDateTime(slotProps.option.scheduled_at) }}
                </small>
              </div>
            </template>
          </Select>

          <small v-if="fieldError('appointment_id')" class="text-red-500">
            {{ fieldError('appointment_id') }}
          </small>

          <Message
            v-if="!appointmentLoading && confirmedAppointments.length === 0"
            severity="info"
            class="mt-3"
          >
            No confirmed appointments are available for examination.
          </Message>
        </div>

        <!-- Appointment EDIT readonly -->
        <div v-else>
          <label class="block font-bold mb-3"> Appointment </label>
          <InputText :model-value="`#${selectedExamination.appointment_id}`" fluid disabled />
        </div>

        <!-- Patient / Doctor readonly -->
        <div v-if="selectedExamination" class="grid grid-cols-12 gap-4">
          <div class="col-span-12 md:col-span-6">
            <label class="block font-bold mb-3"> Patient </label>
            <InputText
              :model-value="selectedExamination.patient_id?.full_name ?? ''"
              disabled
              fluid
            />
          </div>

          <div class="col-span-12 md:col-span-6">
            <label class="block font-bold mb-3"> Doctor </label>
            <InputText :model-value="selectedExamination.doctor_id?.name ?? ''" disabled fluid />
          </div>
        </div>

        <div>
          <label for="diagnosis" class="block font-bold mb-3"> Diagnosis </label>
          <Textarea
            id="diagnosis"
            v-model="form.diagnosis"
            rows="5"
            autofocus
            fluid
            :invalid="(submitted && !form.diagnosis.trim()) || !!fieldError('diagnosis')"
          />
          <small v-if="fieldError('diagnosis')" class="text-red-500">
            {{ fieldError('diagnosis') }}
          </small>
          <small v-else-if="submitted && !form.diagnosis.trim()" class="text-red-500">
            Diagnosis is required.
          </small>
        </div>

        <div>
          <label for="notes" class="block font-bold mb-3"> Notes </label>
          <Textarea
            id="notes"
            v-model="form.notes"
            rows="4"
            fluid
            :invalid="!!fieldError('notes')"
          />
          <small v-if="fieldError('notes')" class="text-red-500">
            {{ fieldError('notes') }}
          </small>
        </div>
      </div>

      <template #footer>
        <Button label="Cancel" icon="pi pi-times" text :disabled="saving" @click="hideDialog" />
        <Button
          label="Save"
          icon="pi pi-check"
          :loading="saving"
          :disabled="!selectedExamination && confirmedAppointments.length === 0"
          @click="saveExamination"
        />
      </template>
    </Dialog>

    <!-- View Examination Details Dialog -->
    <Dialog
      v-model:visible="detailDialog"
      :style="{ width: '550px' }"
      header="Examination Details"
      modal
    >
      <div v-if="detailLoading" class="flex justify-center py-8">
        <ProgressSpinner />
      </div>

      <div v-else-if="detailExamination" class="flex flex-col gap-5">
        <div class="flex items-center gap-4 pb-4 border-b border-surface">
          <div
            class="w-14 h-14 rounded-full bg-primary/10 text-primary flex items-center justify-center text-xl font-bold shrink-0"
          >
            {{ detailExamination.patient_id?.full_name?.charAt(0).toUpperCase() ?? 'E' }}
          </div>
          <div class="flex flex-col">
            <span class="text-lg font-semibold">
              {{ detailExamination.patient_id?.full_name ?? '—' }}
            </span>
            <span class="text-sm text-muted-color">
              {{ detailExamination.patient_id?.code ?? '—' }} · Appointment #{{
                detailExamination.appointment_id
              }}
            </span>
            <div class="mt-1">
              <Tag :value="`Exam #${detailExamination.id}`" severity="info" />
            </div>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <span class="text-xs text-muted-color uppercase font-semibold block mb-1">
              Doctor
            </span>
            <span class="font-medium text-sm">
              {{ detailExamination.doctor_id?.name ?? '—' }}
            </span>
          </div>

          <div>
            <span class="text-xs text-muted-color uppercase font-semibold block mb-1">
              Doctor License
            </span>
            <span class="font-medium font-mono text-sm">
              {{ detailExamination.doctor_id?.license_number ?? '—' }}
            </span>
          </div>

          <div>
            <span class="text-xs text-muted-color uppercase font-semibold block mb-1">
              Examined At
            </span>
            <span class="font-medium text-sm">
              {{ formatDateTime(detailExamination.examined_at) }}
            </span>
          </div>

          <div>
            <span class="text-xs text-muted-color uppercase font-semibold block mb-1">
              Appointment ID
            </span>
            <span class="font-medium font-mono text-sm">
              #{{ detailExamination.appointment_id }}
            </span>
          </div>

          <div>
            <span class="text-xs text-muted-color uppercase font-semibold block mb-1">
              Created At
            </span>
            <span class="text-sm text-muted-color">
              {{ detailExamination.created_at ?? '—' }}
            </span>
          </div>

          <div>
            <span class="text-xs text-muted-color uppercase font-semibold block mb-1">
              Updated At
            </span>
            <span class="text-sm text-muted-color">
              {{ detailExamination.updated_at ?? '—' }}
            </span>
          </div>
        </div>

        <div>
          <span class="text-xs text-muted-color uppercase font-semibold block mb-1">
            Diagnosis
          </span>
          <div
            class="p-3 bg-surface-50 dark:bg-surface-800 rounded-lg text-sm leading-relaxed text-surface-700 dark:text-surface-200 whitespace-pre-line font-medium"
          >
            {{ detailExamination.diagnosis }}
          </div>
        </div>

        <div>
          <span class="text-xs text-muted-color uppercase font-semibold block mb-1"> Notes </span>
          <div
            class="p-3 bg-surface-50 dark:bg-surface-800 rounded-lg text-sm leading-relaxed text-surface-700 dark:text-surface-200 whitespace-pre-line"
          >
            {{ detailExamination.notes || 'No notes provided.' }}
          </div>
        </div>
      </div>

      <template #footer>
        <Button label="Close" icon="pi pi-times" text @click="detailDialog = false" />
        <Button
          v-if="auth.can('EXAMINATIONS.UPDATE') && detailExamination"
          label="Edit"
          icon="pi pi-pencil"
          @click="openEditFromView(detailExamination)"
        />
      </template>
    </Dialog>
  </div>
</template>
