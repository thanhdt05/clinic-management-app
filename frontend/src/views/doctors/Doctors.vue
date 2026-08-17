<script setup lang="ts">
import axios from 'axios'
import { useToast } from 'primevue/usetoast'
import { onMounted, ref } from 'vue'

import { doctorService } from '@/services/doctorService'
import { specialtyService } from '@/services/specialtyService'
import { useAuthStore } from '@/stores/auth'

import type { ApiErrorResponse, AuthUser } from '@/types/auth'
import type {
  PaginationMeta,
  Doctor,
  CreateDoctorPayload,
  UpdateDoctorPayload,
} from '@/types/doctor'
import type { Specialty } from '@/types/specialty'
import { userService } from '@/services/userService'

const auth = useAuthStore()
const toast = useToast()

const doctors = ref<Doctor[]>([])
const doctorUsers = ref<AuthUser[]>([])
const specialties = ref<Specialty[]>([])

const loading = ref(false)
const saving = ref(false)
const catalogLoading = ref(false)
const submitted = ref(false)

const doctorDialog = ref(false)
const deleteDoctorDialog = ref(false)
const viewDoctorDialog = ref(false)

const selectedDoctor = ref<Doctor | null>(null)
const viewingDoctor = ref<Doctor | null>(null)
const viewLoading = ref(false)

const errors = ref<Record<string, string[]>>({})

const form = ref({
  user_id: null as number | null,
  specialty_id: null as number | null,
  license_number: '',
  bio: '',
})

const meta = ref<PaginationMeta>({
  current_page: 1,
  per_page: 10,
  total: 0,
  last_page: 1,
})

onMounted(() => {
  loadDoctors()
})

function fieldError(field: string) {
  return errors.value[field]?.[0]
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

  showApiError(error, 'Unable to save doctor.')
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

async function loadDoctors(page = 1) {
  loading.value = true

  try {
    const response = await doctorService.getAll(page)

    doctors.value = response.data
    meta.value = response.meta
  } catch (error) {
    showApiError(error, 'Unable to load doctors.')
  } finally {
    loading.value = false
  }
}

async function loadDoctorUsers() {
  const assignedUserIds = new Set<number>()
  let docPage = 1
  let docLastPage = 1

  do {
    const docRes = await doctorService.getAll(docPage)
    docRes.data.forEach((doc) => {
      if (doc.user?.id) {
        assignedUserIds.add(doc.user.id)
      }
    })
    docLastPage = docRes.meta.last_page
    docPage++
  } while (docPage <= docLastPage)

  const result: AuthUser[] = []
  let page = 1
  let lastPage = 1

  do {
    const response = await userService.getAll(page)

    result.push(
      ...response.data.filter(
        (user) =>
          user.is_active &&
          user.role?.name?.toUpperCase() === 'DOCTOR' &&
          !assignedUserIds.has(user.id),
      ),
    )

    page++
    lastPage = response.meta.last_page
  } while (page <= lastPage)

  doctorUsers.value = result
}

async function loadAllSpecialties() {
  if (specialties.value.length > 0) {
    return
  }

  const result: Specialty[] = []
  let page = 1
  let lastPage = 1

  do {
    const response = await specialtyService.getAll(page)
    result.push(...response.data)
    lastPage = response.meta.last_page
    page++
  } while (page <= lastPage)

  specialties.value = result
}

async function loadCreateCatalogs() {
  catalogLoading.value = true

  try {
    await Promise.all([loadDoctorUsers(), loadAllSpecialties()])
  } catch (error) {
    showApiError(error, 'Unable to load catalog data.')
    throw error
  } finally {
    catalogLoading.value = false
  }
}

async function openNew() {
  selectedDoctor.value = null
  submitted.value = false
  errors.value = {}

  form.value = {
    user_id: null,
    specialty_id: null,
    license_number: '',
    bio: '',
  }

  doctorDialog.value = true

  try {
    await loadCreateCatalogs()
  } catch {
    doctorDialog.value = false
  }
}

function hideDialog() {
  doctorDialog.value = false
  submitted.value = false
  errors.value = {}
}

async function saveDoctor() {
  submitted.value = true
  errors.value = {}

  if (!form.value.user_id || !form.value.specialty_id || !form.value.license_number.trim()) {
    return
  }

  if (!selectedDoctor.value && !form.value.user_id) {
    return
  }

  saving.value = true

  try {
    if (selectedDoctor.value) {
      const payload: UpdateDoctorPayload = {
        specialty_id: form.value.specialty_id,

        license_number: form.value.license_number.trim(),

        bio: form.value.bio.trim() ? form.value.bio.trim() : null,
      }

      const response = await doctorService.update(selectedDoctor.value.id, payload)

      toast.add({
        severity: 'success',
        summary: 'Successful',
        detail: response.message,
        life: 3000,
      })
    } else {
      const payload: CreateDoctorPayload = {
        user_id: form.value.user_id!,
        specialty_id: form.value.specialty_id!,
        license_number: form.value.license_number.trim(),
        bio: form.value.bio.trim() ? form.value.bio.trim() : null,
      }

      const response = await doctorService.create(payload)

      toast.add({
        severity: 'success',
        summary: 'Successful',
        detail: response.message,
        life: 3000,
      })
    }

    doctorDialog.value = false
    await loadDoctors(selectedDoctor.value ? meta.value.current_page : 1)
  } catch (error) {
    handleValidationError(error)
  } finally {
    saving.value = false
  }
}

async function viewDoctor(doctor: Doctor) {
  viewingDoctor.value = null
  viewDoctorDialog.value = true
  viewLoading.value = true

  try {
    const response = await doctorService.getOne(doctor.id)
    viewingDoctor.value = response.data
  } catch (error) {
    showApiError(error, 'Unable to load doctor details.')
    viewDoctorDialog.value = false
  } finally {
    viewLoading.value = false
  }
}

function openEditFromView(doctor: Doctor) {
  viewDoctorDialog.value = false
  editDoctor(doctor)
}

async function editDoctor(doctor: Doctor) {
  selectedDoctor.value = doctor
  submitted.value = false
  errors.value = {}

  form.value = {
    user_id: doctor.user?.id ?? null,
    specialty_id: doctor.specialty?.id ?? null,
    license_number: doctor.license_number,
    bio: doctor.bio ?? '',
  }

  doctorDialog.value = true

  if (specialties.value.length === 0) {
    catalogLoading.value = true

    try {
      await loadAllSpecialties()
    } catch (error) {
      showApiError(error, 'Unable to load specialties.')

      doctorDialog.value = false
    } finally {
      catalogLoading.value = false
    }
  }
}

function confirmDeleteSelected(doctor: Doctor) {
  selectedDoctor.value = doctor
  deleteDoctorDialog.value = true
}

async function deleteDoctor() {
  if (!selectedDoctor.value) {
    return
  }

  try {
    const response = await doctorService.remove(selectedDoctor.value.id)

    deleteDoctorDialog.value = false

    toast.add({
      severity: 'success',
      summary: 'Successful',
      detail: response.message,
      life: 3000,
    })

    const nextPage =
      doctors.value.length === 1 && meta.value.current_page > 1
        ? meta.value.current_page - 1
        : meta.value.current_page

    await loadDoctors(nextPage)
  } catch (error) {
    showApiError(error, 'Unable to delete doctor.')
  }
}

function onPage(event: { page: number }) {
  loadDoctors(event.page + 1)
}
</script>

<template>
  <div>
    <Toast />
    <div class="card">
      <Toolbar class="mb-6">
        <template #start>
          <Button
            v-if="auth.can('DOCTORS.CREATE')"
            label="New"
            icon="pi pi-plus"
            severity="secondary"
            class="mr-2"
            @click="openNew"
          />
        </template>
      </Toolbar>

      <DataTable
        :value="doctors"
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
          of {totalRecords} doctors
        "
        @page="onPage"
      >
        <template #header>
          <div class="flex flex-wrap gap-2 items-center justify-between">
            <h4 class="m-0">Manage Doctors</h4>
            <span class="text-muted-color">
              {{ meta.total }}
              doctors
            </span>
          </div>
        </template>

        <template #empty> No doctors found. </template>

        <Column header="Doctor" style="min-width: 15rem">
          <template #body="slotProps">
            <div class="flex flex-col">
              <span class="font-medium">
                {{ slotProps.data.user?.name ?? '—' }}
              </span>

              <span class="text-sm text-muted-color">
                {{ slotProps.data.user?.email ?? '' }}
              </span>
            </div>
          </template>
        </Column>

        <Column header="Specialty" style="min-width: 12rem">
          <template #body="slotProps">
            <Tag :value="slotProps.data.specialty?.name ?? '—'" severity="info" />
          </template>
        </Column>

        <Column field="license_number" header="License Number" style="min-width: 13rem" />

        <Column header="Bio" style="min-width: 16rem">
          <template #body="slotProps">
            <span v-if="slotProps.data.bio">
              {{
                slotProps.data.bio.length > 60
                  ? `${slotProps.data.bio.slice(0, 60)}...`
                  : slotProps.data.bio
              }}
            </span>

            <span v-else class="text-muted-color"> — </span>
          </template>
        </Column>

        <Column :exportable="false" style="min-width: 12rem">
          <template #body="slotProps">
            <Button
              v-if="auth.can('DOCTORS.FINDONE')"
              icon="pi pi-eye"
              outlined
              rounded
              severity="info"
              class="mr-2"
              @click="viewDoctor(slotProps.data)"
            />
            <Button
              v-if="auth.can('DOCTORS.UPDATE')"
              icon="pi pi-pencil"
              outlined
              rounded
              class="mr-2"
              @click="editDoctor(slotProps.data)"
            />
            <Button
              v-if="auth.can('DOCTORS.DELETE')"
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

    <!-- View Doctor Details Dialog -->
    <Dialog
      v-model:visible="viewDoctorDialog"
      :style="{ width: '500px' }"
      header="Doctor Details"
      modal
    >
      <div v-if="viewLoading" class="flex justify-center py-8">
        <ProgressSpinner />
      </div>

      <div v-else-if="viewingDoctor" class="flex flex-col gap-5">
        <div class="flex items-center gap-4 pb-4 border-b border-surface">
          <div
            class="w-14 h-14 rounded-full bg-primary/10 text-primary flex items-center justify-center text-xl font-bold"
          >
            {{ viewingDoctor.user?.name?.charAt(0).toUpperCase() ?? 'D' }}
          </div>
          <div class="flex flex-col">
            <span class="text-lg font-semibold">{{ viewingDoctor.user?.name ?? '—' }}</span>
            <span class="text-sm text-muted-color">{{ viewingDoctor.user?.email ?? '—' }}</span>
            <div class="mt-1">
              <Tag
                :value="viewingDoctor.user?.is_active ? 'Active' : 'Inactive'"
                :severity="viewingDoctor.user?.is_active ? 'success' : 'danger'"
              />
            </div>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <span class="text-xs text-muted-color uppercase font-semibold block mb-1"
              >Specialty</span
            >
            <Tag :value="viewingDoctor.specialty?.name ?? '—'" severity="info" />
          </div>

          <div>
            <span class="text-xs text-muted-color uppercase font-semibold block mb-1"
              >License Number</span
            >
            <span class="font-medium font-mono text-sm">{{ viewingDoctor.license_number }}</span>
          </div>

          <div>
            <span class="text-xs text-muted-color uppercase font-semibold block mb-1"
              >Created At</span
            >
            <span class="text-sm text-muted-color">{{ viewingDoctor.created_at ?? '—' }}</span>
          </div>

          <div>
            <span class="text-xs text-muted-color uppercase font-semibold block mb-1"
              >Updated At</span
            >
            <span class="text-sm text-muted-color">{{ viewingDoctor.updated_at ?? '—' }}</span>
          </div>
        </div>

        <!-- Bio -->
        <div>
          <span class="text-xs text-muted-color uppercase font-semibold block mb-1">Biography</span>
          <div
            class="p-3 bg-surface-50 dark:bg-surface-800 rounded-lg text-sm leading-relaxed text-surface-700 dark:text-surface-200"
          >
            {{ viewingDoctor.bio || 'No biography provided.' }}
          </div>
        </div>
      </div>

      <template #footer>
        <Button label="Close" icon="pi pi-times" text @click="viewDoctorDialog = false" />
        <Button
          v-if="auth.can('DOCTORS.UPDATE') && viewingDoctor"
          label="Edit"
          icon="pi pi-pencil"
          @click="openEditFromView(viewingDoctor)"
        />
      </template>
    </Dialog>

    <Dialog
      v-model:visible="doctorDialog"
      :style="{ width: '450px' }"
      :header="selectedDoctor ? 'Edit Doctor' : 'Doctor Details'"
      modal
    >
      <div v-if="catalogLoading" class="flex justify-center py-8">
        <ProgressSpinner />
      </div>

      <div v-else class="flex flex-col gap-6">
        <div v-if="!selectedDoctor">
          <label for="doctor-user" class="block font-bold mb-3">User</label>

          <Select
            id="doctor-user"
            v-model="form.user_id"
            :options="doctorUsers"
            option-label="name"
            option-value="id"
            filter
            filter-placeholder="
              Search users
            "
            placeholder="
              Select a doctor user
            "
            fluid
            :invalid="(submitted && !form.user_id) || !!fieldError('user_id')"
          >
            <template #value="slotProps">
              <span v-if="slotProps.value">
                {{ doctorUsers.find((user) => user.id === slotProps.value)?.name }}
              </span>

              <span v-else> Select a doctor user </span>
            </template>

            <template #option="slotProps">
              <div class="flex flex-col">
                <span>
                  {{ slotProps.option.name }}
                </span>

                <small class="text-muted-color">
                  {{ slotProps.option.email }}
                </small>
              </div>
            </template>
          </Select>
        </div>

        <div v-else>
          <label class="block font-bold mb-3"> User </label>

          <InputText :model-value="selectedDoctor.user?.name ?? ''" disabled fluid />

          <small class="text-muted-color"> Doctor account cannot be changed after creation. </small>
        </div>

        <!-- Specialty -->
        <div>
          <label for="specialty" class="block font-bold mb-3">Specialty</label>
          <Select
            id="specialty"
            v-model="form.specialty_id"
            :options="specialties"
            option-label="name"
            option-value="id"
            fluid
            filter
            placeholder="
              Select a specialty
            "
            :invalid="(submitted && !form.specialty_id) || !!fieldError('specialty_id')"
          />
          <small v-if="fieldError('specialty_id')" class="text-red-500">{{
            fieldError('specialty_id')
          }}</small>

          <small v-else-if="submitted && !form.specialty_id" class="text-red-500">
            Specialty is required.
          </small>
        </div>

        <!-- License -->
        <div>
          <label for="license_number" class="block font-bold mb-3">License Number</label>
          <InputText
            id="license_number"
            v-model.trim="form.license_number"
            fluid
            :invalid="(submitted && !form.license_number) || !!fieldError('license_number')"
          />
          <small v-if="fieldError('license_number')" class="text-red-500">
            {{ fieldError('license_number') }}
          </small>
          <small v-else-if="submitted && !form.license_number" class="text-red-500">
            License number is required.
          </small>
        </div>

        <!-- Bio -->
        <div>
          <label for="bio" class="block font-bold mb-3"> Bio </label>

          <Textarea id="bio" v-model="form.bio" rows="5" fluid :invalid="!!fieldError('bio')" />

          <small v-if="fieldError('bio')" class="text-red-500">
            {{ fieldError('bio') }}
          </small>
        </div>
      </div>

      <template #footer>
        <Button label="Cancel" icon="pi pi-times" text @click="hideDialog" />
        <Button label="Save" icon="pi pi-check" :loading="saving" @click="saveDoctor" />
      </template>
    </Dialog>

    <Dialog v-model:visible="deleteDoctorDialog" :style="{ width: '450px' }" header="Confirm" modal>
      <div class="flex items-center gap-4">
        <i class="pi pi-exclamation-triangle text-3xl!" />
        <span v-if="selectedDoctor"
          >Are you sure you want to delete <b>{{ selectedDoctor.user?.name }}</b
          >?</span
        >
      </div>
      <template #footer>
        <Button label="No" icon="pi pi-times" text @click="deleteDoctorDialog = false" />
        <Button label="Yes" icon="pi pi-check" severity="danger" @click="deleteDoctor" />
      </template>
    </Dialog>
  </div>
</template>
