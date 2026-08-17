import api from '@/services/api'
import type { ApiResponse } from '@/types/auth'

import type {
  PaginatedPatientResponse,
  Patient,
  PatientPayload,
  PatientQuery,
} from '@/types/patient'

export const patientService = {
  async getAll(params: PatientQuery = {}) {
    const response = await api.get<PaginatedPatientResponse>('/patients', { params })
    return response.data
  },

  async getOne(id: number) {
    const response = await api.get<ApiResponse<Patient>>(`/patients/${id}`)
    return response.data
  },

  async create(payload: PatientPayload) {
    const response = await api.post<ApiResponse<Patient>>('/patients', payload)
    return response.data
  },

  async update(id: number, payload: PatientPayload) {
    const response = await api.patch<ApiResponse<Patient>>(`/patients/${id}`, payload)
    return response.data
  },

  async remove(id: number) {
    const response = await api.delete<ApiResponse<[]>>(`/patients/${id}`)
    return response.data
  },
}
