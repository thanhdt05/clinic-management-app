import type { ApiResponse } from './auth'

export interface DoctorUser {
  id: number
  name: string
  email: string
  is_active: boolean
}

export interface DoctorSpecialty {
  id: number
  name: string
}

export interface Doctor {
  id: number
  user: DoctorUser | null
  specialty: DoctorSpecialty | null
  license_number: string
  bio: string | null
  created_at: string | null
  updated_at: string | null
}

export interface CreateDoctorPayload {
  user_id: number
  specialty_id: number
  license_number: string
  bio: string | null
}

export interface UpdateDoctorPayload {
  specialty_id: number
  license_number: string
  bio: string | null
}

export interface PaginationMeta {
  current_page: number
  per_page: number
  total: number
  last_page: number
}

export interface PaginatedDoctorResponse extends ApiResponse<Doctor[]> {
  meta: PaginationMeta
}
