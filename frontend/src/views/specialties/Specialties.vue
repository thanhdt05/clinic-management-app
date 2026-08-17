<script setup lang="ts">
import axios from 'axios'
import { useToast } from 'primevue/usetoast'
import { onMounted, ref } from 'vue'

import { specialtyService } from '@/services/specialtyService'
import { useAuthStore } from '@/stores/auth'

import type { ApiErrorResponse } from '@/types/auth'
import type { PaginationMeta, Specialty, SpecialtyPayload } from '@/types/specialty'

const auth = useAuthStore()
const toast = useToast()

const specialties = ref<Specialty[]>([])
const loading = ref(false)
const saving = ref(false)
const submitted = ref(false)

const specialtyDialog = ref(false)
const deleteSpecialtyDialog = ref(false)

const selectedSpecialty = ref<Specialty | null>(null)

const errors = ref<Record<string, string[]>>({})

const form = ref({
  name: '',
  description: '',
})

const meta = ref<PaginationMeta>({
  current_page: 1,
  per_page: 10,
  total: 0,
  last_page: 1,
})

onMounted(() => {
  loadSpecialties()
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

  showApiError(error, 'Unable to save specialty.')
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

async function loadSpecialties(page = 1) {
  loading.value = true

  try {
    const response = await specialtyService.getAll(page)

    specialties.value = response.data
    meta.value = response.meta
  } catch (error) {
    showApiError(error, 'Unable to load specialties.')
  } finally {
    loading.value = false
  }
}

function openNew() {
  selectedSpecialty.value = null
  submitted.value = false
  errors.value = {}

  form.value = {
    name: '',
    description: '',
  }

  specialtyDialog.value = true
}

function hideDialog() {
  specialtyDialog.value = false
  submitted.value = false
  errors.value = {}
}

async function saveSpecialty() {
  submitted.value = true
  errors.value = {}

  if (!form.value.name.trim()) {
    return
  }

  saving.value = true

  try {
    if (selectedSpecialty.value) {
      const response = await specialtyService.update(selectedSpecialty.value.id, form.value)

      toast.add({
        severity: 'success',
        summary: 'Successful',
        detail: response.message,
        life: 3000,
      })
    } else {
      const response = await specialtyService.create(form.value)

      toast.add({
        severity: 'success',
        summary: 'Successful',
        detail: response.message,
        life: 3000,
      })
    }

    specialtyDialog.value = false
    await loadSpecialties(selectedSpecialty.value ? meta.value.current_page : 1)
  } catch (error) {
    handleValidationError(error)
  } finally {
    saving.value = false
  }
}

function editSpecialty(specialty: Specialty) {
  selectedSpecialty.value = specialty
  submitted.value = false
  errors.value = {}

  form.value = {
    name: specialty.name,
    description: specialty.description ?? '',
  }

  specialtyDialog.value = true
}

function confirmDeleteSelected(specialty: Specialty) {
  selectedSpecialty.value = specialty
  deleteSpecialtyDialog.value = true
}

async function deleteSpecialty() {
  if (!selectedSpecialty.value) {
    return
  }

  try {
    const response = await specialtyService.remove(selectedSpecialty.value.id)

    deleteSpecialtyDialog.value = false

    toast.add({
      severity: 'success',
      summary: 'Successful',
      detail: response.message,
      life: 3000,
    })

    const nextPage =
      specialties.value.length === 1 && meta.value.current_page > 1
        ? meta.value.current_page - 1
        : meta.value.current_page

    await loadSpecialties(nextPage)
  } catch (error) {
    showApiError(error, 'Unable to delete specialty.')
  }
}

function onPage(event: { page: number }) {
  loadSpecialties(event.page + 1)
}
</script>

<template>
  <div>
    <Toast />
    <div class="card">
      <Toolbar class="mb-6">
        <template #start>
          <Button
            v-if="auth.can('SPECIALTIES.CREATE')"
            label="New"
            icon="pi pi-plus"
            severity="secondary"
            class="mr-2"
            @click="openNew"
          />
        </template>
      </Toolbar>

      <DataTable
        :value="specialties"
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
          of {totalRecords} specialties
        "
        @page="onPage"
      >
        <template #header>
          <div class="flex flex-wrap gap-2 items-center justify-between">
            <h4 class="m-0">Manage Specialties</h4>
            <span class="text-muted-color">
              {{ meta.total }}
              specialties
            </span>
          </div>
        </template>

        <template #empty> No specialties found. </template>

        <Column field="name" header="Name" style="min-width: 16rem"></Column>
        <Column field="description" header="Description" style="min-width: 22rem">
          <template #body="slotProps">
            <span v-if="slotProps.data.description">
              {{ slotProps.data.description }}
            </span>
            <span v-else class="text-muted-color">____</span>
          </template>
        </Column>

        <Column field="updated_at" header="Updated" style="min-width: 12rem" />

        <Column :exportable="false" style="min-width: 10rem">
          <template #body="slotProps">
            <Button
              v-if="auth.can('SPECIALTIES.UPDATE')"
              icon="pi pi-pencil"
              outlined
              rounded
              class="mr-2"
              @click="editSpecialty(slotProps.data)"
            />
            <Button
              v-if="auth.can('SPECIALTIES.DELETE')"
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
      v-model:visible="specialtyDialog"
      :style="{ width: '450px' }"
      :header="selectedSpecialty ? 'Edit Specialty' : 'Add New Specialty'"
      modal
    >
      <div class="flex flex-col gap-6">
        <div>
          <label for="name" class="block font-bold mb-3">Name</label>
          <InputText
            id="name"
            v-model.trim="form.name"
            autofocus
            :invalid="(submitted && !form.name) || !!fieldError('name')"
            fluid
          />
          <small v-if="fieldError('name')" class="text-red-500">{{ fieldError('name') }}</small>
          <small v-else-if="submitted && !form.name" class="text-red-500">
            Specialty name is required.
          </small>
        </div>
        <div>
          <label for="description" class="block font-bold mb-3">Description</label>
          <Textarea
            id="description"
            v-model="form.description"
            rows="5"
            fluid
            :invalid="!!fieldError('description')"
          />
          <small v-if="fieldError('description')" class="text-red-500">
            {{ fieldError('description') }}
          </small>
        </div>
      </div>

      <template #footer>
        <Button label="Cancel" icon="pi pi-times" text @click="hideDialog" />
        <Button label="Save" icon="pi pi-check" :loading="saving" @click="saveSpecialty" />
      </template>
    </Dialog>

    <Dialog
      v-model:visible="deleteSpecialtyDialog"
      :style="{ width: '450px' }"
      header="Confirm"
      modal
    >
      <div class="flex items-center gap-4">
        <i class="pi pi-exclamation-triangle text-3xl!" />
        <span v-if="selectedSpecialty"
          >Are you sure you want to delete <b>{{ selectedSpecialty.name }}</b
          >?</span
        >
      </div>
      <template #footer>
        <Button label="No" icon="pi pi-times" text @click="deleteSpecialtyDialog = false" />
        <Button label="Yes" icon="pi pi-check" severity="danger" @click="deleteSpecialty" />
      </template>
    </Dialog>
  </div>
</template>
