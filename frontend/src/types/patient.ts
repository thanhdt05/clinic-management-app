import type { ApiResponse } from '@/types/auth'

export type PatientGender = 'male' | 'female' | 'other'

export interface Patient {
  id: number
  code: string
  full_name: string
  gender: PatientGender
  date_of_birth: string
  phone: string
  email: string | null
  address: string | null
  created_at: string
  updated_at: string
  deleted_at: string | null
}

export interface PatientPayload {
  full_name: string
  gender: PatientGender
  date_of_birth: string
  phone: string
  email: string | null
  address: string | null
}

export interface PatientQuery {
  page?: number
  per_page?: number
  q?: string
}

export interface PaginationMeta {
  current_page: number
  per_page: number
  total: number
  last_page: number
}

export interface PaginatedPatientResponse extends ApiResponse<Patient[]> {
  meta: PaginationMeta
}
