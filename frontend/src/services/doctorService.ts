import api from '@/services/api'

import type { ApiResponse } from '@/types/auth'
import type {
  Doctor,
  PaginatedDoctorResponse,
  UpdateDoctorPayload,
  CreateDoctorPayload,
} from '@/types/doctor'

export const doctorService = {
  async getAll(page = 1) {
    const response = await api.get<PaginatedDoctorResponse>('/doctors', { params: { page } })
    return response.data
  },

  async getOne(id: number) {
    const response = await api.get<ApiResponse<Doctor>>(`/doctors/${id}`)
    return response.data
  },

  async create(payload: CreateDoctorPayload) {
    const response = await api.post<ApiResponse<Doctor>>('/doctors', payload)
    return response.data
  },

  async update(id: number, payload: UpdateDoctorPayload) {
    const response = await api.patch<ApiResponse<Doctor>>(`/doctors/${id}`, payload)
    return response.data
  },

  async remove(id: number) {
    const response = await api.delete<ApiResponse<[]>>(`/doctors/${id}`)
    return response.data
  },
}
