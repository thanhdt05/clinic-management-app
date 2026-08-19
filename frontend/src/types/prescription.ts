import type { ApiResponse } from '@/types/auth'

export interface PrescriptionPatient {
  id: number
  code: string
  full_name: string
}

export interface PrescriptionExamination {
  id: number
  patient: PrescriptionPatient
}

export interface PrescriptionDoctor {
  id: number
  name: string
  license_number: string
}

export interface PrescriptionMedicine {
  id: number
  code: string
  name: string
  unit: string
}

export interface PrescriptionItem {
  id: number
  medicine: PrescriptionMedicine
  quantity: number
  dosage: string
  usage_instruction: string | null
}

export interface Prescription {
  id: number
  examinations: PrescriptionExamination
  doctor: PrescriptionDoctor
  notes: string | null
  items: PrescriptionItem[]
  created_at: string
  updated_at: string
}

export interface PrescriptionQuery {
  page?: number
  per_page?: number
  doctor_id?: number
}

export interface CreatePrescriptionPayload {
  examination_id: number
  notes: string | null
}

export interface UpdatePrescriptionPayload {
  notes: string | null
}

export interface AddPrescriptionItemPayload {
  medicine_id: number
  quantity: number
  dosage: string
  usage_instruction: string
}

export interface UpdatePrescriptionItemPayload {
  quantity?: number
  dosage?: string
  usage_instruction?: string
}

export interface PaginationMeta {
  current_page: number
  per_page: number
  total: number
  last_page: number
}

export interface PaginatedPrescriptionResponse extends ApiResponse<Prescription[]> {
  meta: PaginationMeta
}
