import type { ApiResponse } from '@/types/auth'

export type AppointmentStatus = 'scheduled' | 'confirmed' | 'cancelled' | 'completed'

export interface AppointmentPatient {
  id: number
  code: string
  full_name: string
  phone: string
}

export interface AppointmentSpecialty {
  id: number
  name: string
}

export interface AppointmentDoctor {
  id: number
  name: string
  license_number: string
  specialty: AppointmentSpecialty | null
}

export interface Appointment {
  id: number
  scheduled_at: string
  status: AppointmentStatus
  reason: string | null

  patient: AppointmentPatient | null
  doctor: AppointmentDoctor | null

  created_at: string | null
  updated_at: string | null
}

export interface AppointmentQuery {
  page?: number
  per_page?: number
  doctor_id?: number
  patient_id?: number
  date?: string
  status?: AppointmentStatus
}

export interface CreateAppointmentPayload {
  patient_id: number
  doctor_id: number
  scheduled_at: string
  reason: string | null
}

export interface UpdateAppointmentPayload {
  scheduled_at?: string
  reason?: string | null
}

export interface PaginationMeta {
  current_page: number
  per_page: number
  total: number
  last_page: number
}

export interface PaginatedAppointmentResponse extends ApiResponse<Appointment[]> {
  meta: PaginationMeta
}
