import type { ApiResponse, AuthUser } from '@/types/auth'

export type User = AuthUser

export interface CreateUserPayload {
  name: string
  email: string
  password: string
  password_confirm: string
  role_id: number
}

export interface UpdateUserPayload {
  name?: string
  email?: string
  password?: string
  role_id?: number
}

export interface PaginationMeta {
  current_page: number
  per_page: number
  total: number
  last_page: number
}

export interface PaginatedResponse<T> extends ApiResponse<T[]> {
  meta: PaginationMeta
}
