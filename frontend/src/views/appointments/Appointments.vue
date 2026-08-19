<script setup lang="ts">
import axios from 'axios'
import { onMounted, ref } from 'vue'
import { useToast } from 'primevue/usetoast'

import { appointmentService } from '@/services/appointmentService'
import { doctorService } from '@/services/doctorService'
import { patientService } from '@/services/patientService'
import { useAuthStore } from '@/stores/auth'

import type { ApiErrorResponse } from '@/types/auth'
import type {
  Appointment,
  AppointmentStatus,
  CreateAppointmentPayload,
  PaginationMeta,
  UpdateAppointmentPayload,
} from '@/types/appointment'
import type { Doctor } from '@/types/doctor'
import type { Patient } from '@/types/patient'

interface PageEvent {
  page: number
  rows: number
}

interface AutoCompleteEvent {
  query: string
}

const auth = useAuthStore()
const toast = useToast()

const appointments = ref<Appointment[]>([])
const doctors = ref<Doctor[]>([])
const patientSuggestions = ref<Patient[]>([])

const loading = ref(false)
const saving = ref(false)
const detailLoading = ref(false)
const catalogLoading = ref(false)
const submitted = ref(false)

const appointmentDialog = ref(false)
const detailDialog = ref(false)
const statusDialog = ref(false)

const selectedAppointment = ref<Appointment | null>(null)
const detailAppointment = ref<Appointment | null>(null)
const pendingStatus = ref<AppointmentStatus | null>(null)

const errors = ref<Record<string, string[]>>({})

const filterStatus = ref<AppointmentStatus | null>(null)
const filterDate = ref<Date | null>(null)
const filterDoctorId = ref<number | null>(null)

const meta = ref<PaginationMeta>({
  current_page: 1,
  per_page: 10,
  total: 0,
  last_page: 1,
})

const form = ref({
  patient: null as Patient | null,
  doctor_id: null as number | null,
  scheduled_at: null as Date | null,
  reason: '',
})

const statusOptions = [
  { label: 'Scheduled', value: 'scheduled' },
  { label: 'Confirmed', value: 'confirmed' },
  { label: 'Cancelled', value: 'cancelled' },
  { label: 'Completed', value: 'completed' },
]

onMounted(async () => {
  await loadAppointments()

  if (auth.can('DOCTORS.FINDALL')) {
    await loadAllDoctors()
  }
})

async function loadAppointments(page = 1, perPage = meta.value.per_page) {
  loading.value = true

  try {
    const response = await appointmentService.getAll({
      page,
      per_page: perPage,
      ...(filterStatus.value ? { status: filterStatus.value } : {}),
      ...(filterDate.value ? { date: formatDate(filterDate.value) } : {}),
      ...(filterDoctorId.value ? { doctor_id: filterDoctorId.value } : {}),
    })

    appointments.value = response.data
    meta.value = response.meta
  } catch (error) {
    showApiError(error, 'Unable to load appointments.')
  } finally {
    loading.value = false
  }
}

async function loadAllDoctors() {
  catalogLoading.value = true

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
  } finally {
    catalogLoading.value = false
  }
}

async function searchPatients(event: AutoCompleteEvent) {
  try {
    const keyword = event.query.trim()
    const response = await patientService.getAll({
      page: 1,
      per_page: 20,
      ...(keyword ? { q: keyword } : {}),
    })

    patientSuggestions.value = response.data
  } catch (error) {
    showApiError(error, 'Unable to search patients.')
  }
}

async function openNew() {
  selectedAppointment.value = null
  submitted.value = false
  errors.value = {}

  form.value = {
    patient: null,
    doctor_id: null,
    scheduled_at: null,
    reason: '',
  }

  appointmentDialog.value = true

  if (doctors.value.length === 0 && auth.can('DOCTORS.FINDALL')) {
    await loadAllDoctors()
  }
}

function editAppointment(appointment: Appointment) {
  selectedAppointment.value = appointment
  submitted.value = false
  errors.value = {}

  form.value = {
    patient: null,
    doctor_id: appointment.doctor?.id ?? null,
    scheduled_at: new Date(appointment.scheduled_at.replace(' ', 'T')),
    reason: appointment.reason ?? '',
  }

  appointmentDialog.value = true
}

function hideDialog() {
  appointmentDialog.value = false
  submitted.value = false
  errors.value = {}
}

async function saveAppointment() {
  submitted.value = true
  errors.value = {}

  if (!form.value.scheduled_at) {
    return
  }

  if (!selectedAppointment.value && (!form.value.patient || !form.value.doctor_id)) {
    return
  }

  saving.value = true

  try {
    if (selectedAppointment.value) {
      const payload: UpdateAppointmentPayload = {
        scheduled_at: formatDateTime(form.value.scheduled_at),
        reason: form.value.reason.trim() || null,
      }

      const response = await appointmentService.update(selectedAppointment.value.id, payload)
      toast.add({
        severity: 'success',
        summary: 'Successful',
        detail: response.message,
        life: 3000,
      })
    } else {
      const payload: CreateAppointmentPayload = {
        patient_id: form.value.patient!.id,
        doctor_id: form.value.doctor_id!,
        scheduled_at: formatDateTime(form.value.scheduled_at),
        reason: form.value.reason.trim() || null,
      }

      const response = await appointmentService.create(payload)
      toast.add({
        severity: 'success',
        summary: 'Successful',
        detail: response.message,
        life: 3000,
      })
    }

    appointmentDialog.value = false
    await loadAppointments(selectedAppointment.value ? meta.value.current_page : 1)
  } catch (error) {
    showApiError(error, 'Unable to save appointment.')
  } finally {
    saving.value = false
  }
}

async function viewAppointment(appointment: Appointment) {
  detailDialog.value = true
  detailAppointment.value = null
  detailLoading.value = true

  try {
    const response = await appointmentService.getOne(appointment.id)
    detailAppointment.value = response.data
  } catch (error) {
    detailDialog.value = false
    showApiError(error, 'Unable to load appointment details.')
  } finally {
    detailLoading.value = false
  }
}

function openEditFromView(appointment: Appointment) {
  editAppointment(appointment)
  detailDialog.value = false
}

function confirmStatusChange(appointment: Appointment, status: AppointmentStatus) {
  selectedAppointment.value = appointment
  pendingStatus.value = status
  statusDialog.value = true
}

async function changeStatus() {
  if (!selectedAppointment.value || !pendingStatus.value) {
    return
  }

  saving.value = true

  try {
    const response = await appointmentService.updateStatus(
      selectedAppointment.value.id,
      pendingStatus.value,
    )

    statusDialog.value = false
    toast.add({ severity: 'success', summary: 'Successful', detail: response.message, life: 3000 })
    await loadAppointments(meta.value.current_page)
  } catch (error) {
    showApiError(error, 'Unable to update appointment status.')
  } finally {
    saving.value = false
  }
}

function applyFilters() {
  loadAppointments(1, meta.value.per_page)
}

function clearFilters() {
  filterStatus.value = null
  filterDate.value = null
  filterDoctorId.value = null
  loadAppointments(1, meta.value.per_page)
}

function onPage(event: PageEvent) {
  loadAppointments(event.page + 1, event.rows)
}

function fieldError(field: string) {
  return errors.value[field]?.[0]
}

function statusLabel(status: AppointmentStatus) {
  return statusOptions.find((option) => option.value === status)?.label ?? status
}

function statusSeverity(status: AppointmentStatus) {
  switch (status) {
    case 'scheduled':
      return 'warn'
    case 'confirmed':
      return 'info'
    case 'completed':
      return 'success'
    case 'cancelled':
      return 'danger'
    default:
      return 'secondary'
  }
}

function doctorLabel(doctor: Doctor) {
  const name = doctor.user?.name ?? 'Unknown doctor'
  const specialty = doctor.specialty?.name
  return specialty ? `${name} — ${specialty}` : name
}

function formatDate(date: Date): string {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

function formatDateTime(date: Date): string {
  const hours = String(date.getHours()).padStart(2, '0')
  const minutes = String(date.getMinutes()).padStart(2, '0')
  const seconds = String(date.getSeconds()).padStart(2, '0')
  return `${formatDate(date)} ${hours}:${minutes}:${seconds}`
}

function formatDisplayDateTime(value?: string | null): string {
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
  <div>
    <Toast />

    <div class="card">
      <Toolbar class="mb-6">
        <template #start>
          <Button
            v-if="auth.can('APPOINTMENTS.CREATE')"
            label="New Appointment"
            icon="pi pi-plus"
            severity="secondary"
            @click="openNew"
          />
        </template>
      </Toolbar>

      <div class="card mb-6 !p-4">
        <div class="flex flex-wrap gap-3 items-end">
          <div class="flex flex-col gap-2">
            <label class="font-medium">Status</label>
            <Select
              v-model="filterStatus"
              :options="statusOptions"
              option-label="label"
              option-value="value"
              placeholder="All Statuses"
              show-clear
              class="w-48"
            />
          </div>

          <div class="flex flex-col gap-2">
            <label class="font-medium">Date</label>
            <DatePicker
              v-model="filterDate"
              date-format="yy-mm-dd"
              show-icon
              placeholder="Select date"
            />
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
                  {{ doctorLabel(doctors.find((doctor) => doctor.id === slotProps.value)!) }}
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

      <DataTable
        :value="appointments"
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
        current-page-report-template="Showing {first} to {last} of {totalRecords} appointments"
        @page="onPage"
      >
        <template #header>
          <div class="flex flex-wrap gap-2 items-center justify-between">
            <h4 class="m-0">Manage Appointments</h4>
            <span class="text-muted-color">{{ meta.total }} appointments</span>
          </div>
        </template>

        <template #empty>No appointments found.</template>

        <Column header="Patient" style="min-width: 15rem">
          <template #body="slotProps">
            <div class="flex flex-col">
              <span class="font-medium">{{ slotProps.data.patient?.full_name ?? '—' }}</span>
              <small class="text-muted-color">
                {{ slotProps.data.patient?.code }} · {{ slotProps.data.patient?.phone }}
              </small>
            </div>
          </template>
        </Column>

        <Column header="Doctor" style="min-width: 15rem">
          <template #body="slotProps">
            <div class="flex flex-col">
              <span class="font-medium">{{ slotProps.data.doctor?.name ?? '—' }}</span>
              <small class="text-muted-color">{{
                slotProps.data.doctor?.specialty?.name ?? ''
              }}</small>
            </div>
          </template>
        </Column>

        <Column header="Scheduled At" style="min-width: 13rem">
          <template #body="slotProps">
            {{ formatDisplayDateTime(slotProps.data.scheduled_at) }}
          </template>
        </Column>

        <Column header="Status" style="min-width: 10rem">
          <template #body="slotProps">
            <Tag
              :value="statusLabel(slotProps.data.status)"
              :severity="statusSeverity(slotProps.data.status)"
            />
          </template>
        </Column>

        <Column header="Reason" style="min-width: 15rem">
          <template #body="slotProps">
            {{ slotProps.data.reason ?? '—' }}
          </template>
        </Column>

        <Column :exportable="false" style="min-width: 15rem">
          <template #body="slotProps">
            <Button
              v-if="auth.can('APPOINTMENTS.FINDONE')"
              icon="pi pi-eye"
              outlined
              rounded
              severity="info"
              class="mr-2"
              @click="viewAppointment(slotProps.data)"
            />

            <!-- Only scheduled can edit -->
            <Button
              v-if="slotProps.data.status === 'scheduled' && auth.can('APPOINTMENTS.UPDATE')"
              icon="pi pi-pencil"
              outlined
              rounded
              class="mr-2"
              @click="editAppointment(slotProps.data)"
            />

            <!-- scheduled -> confirmed -->
            <Button
              v-if="slotProps.data.status === 'scheduled' && auth.can('APPOINTMENTS.UPDATESTATUS')"
              icon="pi pi-check"
              outlined
              rounded
              severity="success"
              class="mr-2"
              aria-label="Confirm appointment"
              @click="confirmStatusChange(slotProps.data, 'confirmed')"
            />

            <!-- scheduled / confirmed -> cancelled -->
            <Button
              v-if="
                (slotProps.data.status === 'scheduled' || slotProps.data.status === 'confirmed') &&
                auth.can('APPOINTMENTS.UPDATESTATUS')
              "
              icon="pi pi-times"
              outlined
              rounded
              severity="danger"
              aria-label="Cancel appointment"
              @click="confirmStatusChange(slotProps.data, 'cancelled')"
            />
          </template>
        </Column>
      </DataTable>
    </div>

    <!-- Create/Edit Dialog -->
    <Dialog
      v-model:visible="appointmentDialog"
      :style="{ width: '600px' }"
      :header="selectedAppointment ? 'Edit Appointment' : 'Appointment Details'"
      modal
    >
      <div class="flex flex-col gap-6">
        <div v-if="!selectedAppointment">
          <label class="block font-bold mb-3">Patient</label>
          <AutoComplete
            v-model="form.patient"
            :suggestions="patientSuggestions"
            option-label="full_name"
            dropdown
            force-selection
            fluid
            placeholder="Search patient..."
            :invalid="(submitted && !form.patient) || !!fieldError('patient_id')"
            @complete="searchPatients"
          >
            <template #option="slotProps">
              <div class="flex flex-col">
                <span class="font-medium">{{ slotProps.option.full_name }}</span>
                <small class="text-muted-color">
                  {{ slotProps.option.code }} · {{ slotProps.option.phone }}
                </small>
              </div>
            </template>
          </AutoComplete>
          <small v-if="fieldError('patient_id')" class="text-red-500">
            {{ fieldError('patient_id') }}
          </small>
        </div>

        <div v-else>
          <label class="block font-bold mb-3">Patient</label>
          <InputText
            :model-value="`${selectedAppointment.patient?.full_name ?? ''} (${selectedAppointment.patient?.code ?? ''})`"
            disabled
            fluid
          />
        </div>

        <!-- Doctor CREATE -->
        <div v-if="!selectedAppointment">
          <label class="block font-bold mb-3">Doctor</label>
          <Select
            v-model="form.doctor_id"
            :options="doctors"
            option-value="id"
            filter
            fluid
            placeholder="Select Doctor"
            :loading="catalogLoading"
            :invalid="(submitted && !form.doctor_id) || !!fieldError('doctor_id')"
          >
            <template #value="slotProps">
              <span v-if="slotProps.value">
                {{ doctorLabel(doctors.find((doctor) => doctor.id === slotProps.value)!) }}
              </span>
              <span v-else>Select Doctor</span>
            </template>
            <template #option="slotProps">
              {{ doctorLabel(slotProps.option) }}
            </template>
          </Select>
          <small v-if="fieldError('doctor_id')" class="text-red-500">
            {{ fieldError('doctor_id') }}
          </small>
        </div>

        <!-- Doctor EDIT -->
        <div v-else>
          <label class="block font-bold mb-3">Doctor</label>
          <InputText :model-value="selectedAppointment.doctor?.name ?? ''" disabled fluid />
        </div>

        <!-- Date + Time -->
        <div>
          <label class="block font-bold mb-3">Scheduled At</label>
          <DatePicker
            v-model="form.scheduled_at"
            show-time
            hour-format="24"
            show-icon
            :min-date="new Date()"
            date-format="yy-mm-dd"
            fluid
            :invalid="(submitted && !form.scheduled_at) || !!fieldError('scheduled_at')"
          />
          <small v-if="fieldError('scheduled_at')" class="text-red-500">
            {{ fieldError('scheduled_at') }}
          </small>
        </div>

        <div>
          <label class="block font-bold mb-3">Reason</label>
          <Textarea
            v-model="form.reason"
            rows="4"
            fluid
            placeholder="Reason for appointment"
            :invalid="!!fieldError('reason')"
          />
          <small v-if="fieldError('reason')" class="text-red-500">
            {{ fieldError('reason') }}
          </small>
        </div>
      </div>

      <template #footer>
        <Button label="Cancel" icon="pi pi-times" text :disabled="saving" @click="hideDialog" />
        <Button label="Save" icon="pi pi-check" :loading="saving" @click="saveAppointment" />
      </template>
    </Dialog>

    <!-- View Appointment Details Dialog -->
    <Dialog
      v-model:visible="detailDialog"
      :style="{ width: '550px' }"
      header="Appointment Details"
      modal
    >
      <div v-if="detailLoading" class="flex justify-center py-8">
        <ProgressSpinner />
      </div>

      <div v-else-if="detailAppointment" class="flex flex-col gap-5">
        <div class="flex items-center gap-4 pb-4 border-b border-surface">
          <div
            class="w-14 h-14 rounded-full bg-primary/10 text-primary flex items-center justify-center text-xl font-bold shrink-0"
          >
            {{ detailAppointment.patient?.full_name?.charAt(0).toUpperCase() ?? 'A' }}
          </div>
          <div class="flex flex-col">
            <span class="text-lg font-semibold">
              {{ detailAppointment.patient?.full_name ?? '—' }}
            </span>
            <span class="text-sm text-muted-color">
              {{ detailAppointment.patient?.phone ?? '—' }} ·
              {{ detailAppointment.patient?.code ?? '—' }}
            </span>
            <div class="mt-1 flex items-center gap-2">
              <Tag
                :value="statusLabel(detailAppointment.status)"
                :severity="statusSeverity(detailAppointment.status)"
              />
            </div>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <span class="text-xs text-muted-color uppercase font-semibold block mb-1">
              Doctor
            </span>
            <span class="font-medium text-sm">
              {{ detailAppointment.doctor?.name ?? '—' }}
            </span>
          </div>

          <div>
            <span class="text-xs text-muted-color uppercase font-semibold block mb-1">
              Specialty
            </span>
            <Tag :value="detailAppointment.doctor?.specialty?.name ?? '—'" severity="info" />
          </div>

          <div>
            <span class="text-xs text-muted-color uppercase font-semibold block mb-1">
              Scheduled At
            </span>
            <span class="font-medium text-sm">
              {{ formatDisplayDateTime(detailAppointment.scheduled_at) }}
            </span>
          </div>

          <div>
            <span class="text-xs text-muted-color uppercase font-semibold block mb-1">
              Doctor License
            </span>
            <span class="font-medium font-mono text-sm">
              {{ detailAppointment.doctor?.license_number ?? '—' }}
            </span>
          </div>

          <div>
            <span class="text-xs text-muted-color uppercase font-semibold block mb-1">
              Created At
            </span>
            <span class="text-sm text-muted-color">
              {{ detailAppointment.created_at ?? '—' }}
            </span>
          </div>

          <div>
            <span class="text-xs text-muted-color uppercase font-semibold block mb-1">
              Updated At
            </span>
            <span class="text-sm text-muted-color">
              {{ detailAppointment.updated_at ?? '—' }}
            </span>
          </div>
        </div>

        <div>
          <span class="text-xs text-muted-color uppercase font-semibold block mb-1">
            Reason for Visit
          </span>
          <div
            class="p-3 bg-surface-50 dark:bg-surface-800 rounded-lg text-sm leading-relaxed text-surface-700 dark:text-surface-200"
          >
            {{ detailAppointment.reason || 'No reason provided.' }}
          </div>
        </div>
      </div>

      <template #footer>
        <Button label="Close" icon="pi pi-times" text @click="detailDialog = false" />
        <Button
          v-if="
            auth.can('APPOINTMENTS.UPDATE') &&
            detailAppointment &&
            detailAppointment.status === 'scheduled'
          "
          label="Edit"
          icon="pi pi-pencil"
          @click="openEditFromView(detailAppointment)"
        />
      </template>
    </Dialog>

    <!-- Status Confirmation -->
    <Dialog v-model:visible="statusDialog" :style="{ width: '450px' }" header="Confirm" modal>
      <div class="flex items-center gap-4">
        <i class="pi pi-exclamation-triangle !text-3xl" />
        <span v-if="selectedAppointment && pendingStatus">
          Change appointment for
          <b>{{ selectedAppointment.patient?.full_name }}</b>
          to
          <b>{{ statusLabel(pendingStatus) }}</b
          >?
        </span>
      </div>

      <template #footer>
        <Button label="No" icon="pi pi-times" text @click="statusDialog = false" />
        <Button
          label="Yes"
          icon="pi pi-check"
          :severity="pendingStatus === 'cancelled' ? 'danger' : 'success'"
          :loading="saving"
          @click="changeStatus"
        />
      </template>
    </Dialog>
  </div>
</template>
