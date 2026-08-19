import type { ApiResponse } from '@/types/auth'

export interface ExaminationPatient {
  id: number
  code: string
  full_name: string
}

export interface ExaminationDoctor {
  id: number
  name: string
  license_number: string
}

export interface Examination {
  id: number
  appointment_id: number

  patient_id: ExaminationPatient
  doctor_id: ExaminationDoctor

  diagnosis: string
  notes: string | null
  examined_at: string

  created_at: string
  updated_at: string
}

export interface ExaminationQuery {
  page?: number
  per_page?: number
  doctor_id?: number
  patient_id?: number
}

export interface CreateExaminationPayload {
  appointment_id: number
  diagnosis: string
  notes: string | null
}

export interface UpdateExaminationPayload {
  diagnosis?: string
  notes?: string
}

export interface PaginationMeta {
  current_page: number
  per_page: number
  total: number
  last_page: number
}

export interface PaginatedExaminationResponse extends ApiResponse<Examination[]> {
  meta: PaginationMeta
}
