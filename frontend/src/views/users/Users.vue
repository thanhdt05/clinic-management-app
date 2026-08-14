<script setup lang="ts">
import axios from 'axios'
import { onMounted, ref } from 'vue'
import { useToast } from 'primevue/usetoast'

import { useAuthStore } from '@/stores/auth'
import { userService } from '@/services/userService'

import type { ApiErrorResponse, Role } from '@/types/auth'
import type { CreateUserPayload, PaginationMeta, UpdateUserPayload, User } from '@/types/user'

const toast = useToast()
const auth = useAuthStore()

const users = ref<User[]>([])
const roles = ref<Role[]>([])

const loading = ref(false)
const submitted = ref(false)
const saving = ref(false)

const userDialog = ref(false)
const deactivateUserDialog = ref(false)

const selectedUser = ref<User | null>(null)

const meta = ref<PaginationMeta>({
  current_page: 1,
  per_page: 10,
  total: 0,
  last_page: 1,
})

const errors = ref<Record<string, string[]>>({})

const form = ref({
  name: '',
  email: '',
  password: '',
  password_confirm: '',
  role_id: null as number | null,
})

onMounted(async () => {
  await loadUsers()

  if (auth.can('ROLES.FINDALL')) {
    await loadRoles()
  }
})

async function loadUsers(page = 1) {
  loading.value = true

  try {
    const response = await userService.getAll(page)

    users.value = response.data
    meta.value = response.meta
  } catch (error) {
    showApiError(error, 'Unable to load users.')
  } finally {
    loading.value = false
  }
}

async function loadRoles() {
  try {
    const response = await userService.getRoles()

    roles.value = response.data
  } catch (error) {
    showApiError(error, 'Unable to load roles.')
  }
}

function openNew() {
  selectedUser.value = null
  submitted.value = false
  errors.value = {}

  form.value = {
    name: '',
    email: '',
    password: '',
    password_confirm: '',
    role_id: null,
  }

  userDialog.value = true
}

function editUser(user: User) {
  selectedUser.value = user
  submitted.value = false
  errors.value = {}

  form.value = {
    name: user.name,
    email: user.email,
    password: '',
    password_confirm: '',
    role_id: user.role.id,
  }

  userDialog.value = true
}

function hideDialog() {
  userDialog.value = false
  submitted.value = false
  errors.value = {}
}

async function saveUser() {
  submitted.value = true
  errors.value = {}

  if (!form.value.name.trim() || !form.value.email.trim() || form.value.role_id === null) {
    return
  }

  if (!selectedUser.value) {
    if (!form.value.password || !form.value.password_confirm) {
      return
    }
  }

  saving.value = true

  try {
    if (selectedUser.value) {
      const payload: UpdateUserPayload = {
        name: form.value.name.trim(),
        email: form.value.email.trim(),
        role_id: form.value.role_id,
      }

      if (form.value.password) {
        payload.password = form.value.password
      }

      const response = await userService.update(selectedUser.value.id, payload)

      toast.add({
        severity: 'success',
        summary: 'Successful',
        detail: response.message,
        life: 3000,
      })
    } else {
      const payload: CreateUserPayload = {
        name: form.value.name.trim(),
        email: form.value.email.trim(),
        password: form.value.password,
        password_confirm: form.value.password_confirm,
        role_id: form.value.role_id,
      }
      const response = await userService.create(payload)

      toast.add({
        severity: 'success',
        summary: 'Successful',
        detail: response.message,
        life: 3000,
      })
    }

    userDialog.value = false
    await loadUsers(selectedUser.value ? meta.value.current_page : 1)
  } catch (error) {
    handleValidationError(error)
  } finally {
    saving.value = false
  }
}

function confirmDeactivateUser(user: User) {
  selectedUser.value = user
  deactivateUserDialog.value = true
}

async function deactivateUser() {
  if (!selectedUser.value) {
    return
  }

  try {
    const response = await userService.deactivate(selectedUser.value.id)

    deactivateUserDialog.value = false

    toast.add({
      severity: 'success',
      summary: 'Successful',
      detail: response.message,
      life: 3000,
    })

    await loadUsers(meta.value.current_page)
  } catch (error) {
    showApiError(error, 'Unable to deactivate user.')
  }
}

async function activateUser(user: User) {
  try {
    const response = await userService.updateStatus(user.id, true)

    toast.add({
      severity: 'success',
      summary: 'Successful',
      detail: response.message,
      life: 3000,
    })

    await loadUsers(meta.value.current_page)
  } catch (error) {
    showApiError(error, 'Unable to activate user.')
  }
}

function onPage(event: { page: number }) {
  loadUsers(event.page + 1)
}

function roleName(user: User) {
  return user.role.display_name || user.role.name
}

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

  showApiError(error, 'Unable to save user.')
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
</script>

<template>
  <div>
    <Toast />

    <div class="card">
      <!-- Sakai CRUD Toolbar -->
      <Toolbar class="mb-6">
        <template #start>
          <Button
            v-if="auth.can('USERS.CREATE')"
            label="New"
            icon="pi pi-plus"
            severity="secondary"
            class="mr-2"
            @click="openNew"
          />
        </template>
      </Toolbar>

      <!-- Sakai CRUD DataTable -->
      <DataTable
        :value="users"
        data-key="id"
        lazy
        paginator
        :rows="meta.per_page"
        :first="(meta.current_page - 1) * meta.per_page"
        :total-records="meta.total"
        :loading="loading"
        paginator-template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
        :current-page-report-template="`Showing {first} to {last} of {totalRecords} users`"
        @page="onPage"
      >
        <template #header>
          <div class="flex flex-wrap gap-2 items-center justify-between">
            <h4 class="m-0">Manage Users</h4>

            <span class="text-muted-color"> {{ meta.total }} users </span>
          </div>
        </template>

        <template #empty> No users found. </template>

        <Column field="name" header="Name" style="min-width: 14rem" />

        <Column field="email" header="Email" style="min-width: 16rem" />

        <Column header="Role" style="min-width: 12rem">
          <template #body="slotProps">
            {{ roleName(slotProps.data) }}
          </template>
        </Column>

        <Column header="Status" style="min-width: 10rem">
          <template #body="slotProps">
            <Tag
              :value="slotProps.data.is_active ? 'ACTIVE' : 'INACTIVE'"
              :severity="slotProps.data.is_active ? 'success' : 'danger'"
            />
          </template>
        </Column>

        <Column :exportable="false" style="min-width: 12rem">
          <template #body="slotProps">
            <Button
              v-if="auth.can('USERS.UPDATE')"
              icon="pi pi-pencil"
              outlined
              rounded
              class="mr-2"
              @click="editUser(slotProps.data)"
            />

            <Button
              v-if="slotProps.data.is_active && auth.can('USERS.DELETE')"
              icon="pi pi-ban"
              outlined
              rounded
              severity="danger"
              @click="confirmDeactivateUser(slotProps.data)"
            />

            <Button
              v-if="!slotProps.data.is_active && auth.can('USERS.UPDATESTATUS')"
              icon="pi pi-check"
              outlined
              rounded
              severity="success"
              @click="activateUser(slotProps.data)"
            />
          </template>
        </Column>
      </DataTable>
    </div>

    <!-- Sakai CRUD create/edit Dialog -->
    <Dialog
      v-model:visible="userDialog"
      :style="{ width: '450px' }"
      :header="selectedUser ? 'Edit User' : 'User Details'"
      modal
    >
      <div class="flex flex-col gap-6">
        <div>
          <label for="name" class="block font-bold mb-3"> Name </label>

          <InputText
            id="name"
            v-model.trim="form.name"
            autofocus
            fluid
            :invalid="(submitted && !form.name) || !!fieldError('name')"
          />

          <small v-if="fieldError('name')" class="text-red-500">
            {{ fieldError('name') }}
          </small>

          <small v-else-if="submitted && !form.name" class="text-red-500">
            Name is required.
          </small>
        </div>

        <div>
          <label for="email" class="block font-bold mb-3"> Email </label>

          <InputText
            id="email"
            v-model.trim="form.email"
            type="email"
            fluid
            :invalid="(submitted && !form.email) || !!fieldError('email')"
          />

          <small v-if="fieldError('email')" class="text-red-500">
            {{ fieldError('email') }}
          </small>

          <small v-else-if="submitted && !form.email" class="text-red-500">
            Email is required.
          </small>
        </div>

        <div>
          <label for="role" class="block font-bold mb-3"> Role </label>

          <Select
            id="role"
            v-model="form.role_id"
            :options="roles"
            option-label="display_name"
            option-value="id"
            placeholder="Select a Role"
            fluid
            :invalid="(submitted && !form.role_id) || !!fieldError('role_id')"
          >
            <template #option="slotProps">
              {{ slotProps.option.display_name || slotProps.option.name }}
            </template>

            <template #value="slotProps">
              <span v-if="slotProps.value">
                {{
                  roles.find((role) => role.id === slotProps.value)?.display_name ||
                  roles.find((role) => role.id === slotProps.value)?.name
                }}
              </span>

              <span v-else> Select a Role </span>
            </template>
          </Select>

          <small v-if="fieldError('role_id')" class="text-red-500">
            {{ fieldError('role_id') }}
          </small>
        </div>

        <div>
          <label for="password" class="block font-bold mb-3">
            {{ selectedUser ? 'New Password' : 'Password' }}
          </label>

          <Password
            id="password"
            v-model="form.password"
            toggle-mask
            :feedback="!selectedUser"
            fluid
            :invalid="(!selectedUser && submitted && !form.password) || !!fieldError('password')"
          />

          <small v-if="fieldError('password')" class="text-red-500">
            {{ fieldError('password') }}
          </small>

          <small v-else-if="selectedUser" class="text-muted-color">
            Leave blank to keep the current password.
          </small>
        </div>

        <div v-if="!selectedUser">
          <label for="password_confirm" class="block font-bold mb-3"> Confirm Password </label>

          <Password
            id="password_confirm"
            v-model="form.password_confirm"
            toggle-mask
            :feedback="false"
            fluid
            :invalid="(submitted && !form.password_confirm) || !!fieldError('password_confirm')"
          />

          <small v-if="fieldError('password_confirm')" class="text-red-500">
            {{ fieldError('password_confirm') }}
          </small>
        </div>
      </div>

      <template #footer>
        <Button label="Cancel" icon="pi pi-times" text @click="hideDialog" />

        <Button label="Save" icon="pi pi-check" :loading="saving" @click="saveUser" />
      </template>
    </Dialog>

    <!-- Sakai CRUD confirm dialog style -->
    <Dialog
      v-model:visible="deactivateUserDialog"
      :style="{ width: '450px' }"
      header="Confirm"
      modal
    >
      <div class="flex items-center gap-4">
        <i class="pi pi-exclamation-triangle !text-3xl" />

        <span v-if="selectedUser">
          Are you sure you want to deactivate
          <b>{{ selectedUser.name }}</b
          >?
        </span>
      </div>

      <template #footer>
        <Button label="No" icon="pi pi-times" text @click="deactivateUserDialog = false" />

        <Button label="Yes" icon="pi pi-check" severity="danger" @click="deactivateUser" />
      </template>
    </Dialog>
  </div>
</template>
