import api from '@/services/api'

import type { ApiResponse } from '@/types/auth'
import type { PaginatedSpecialtyResponse, Specialty, SpecialtyPayload } from '@/types/specialty'

export const specialtyService = {
  async getAll(page = 1) {
    const response = await api.get<PaginatedSpecialtyResponse>('/specialties', { params: { page } })
    return response.data
  },

  async getOne(id: number) {
    const response = await api.get<ApiResponse<Specialty>>(`/specialties/${id}`)
    return response.data
  },

  async create(payload: SpecialtyPayload) {
    const response = await api.post<ApiResponse<Specialty>>('/specialties', payload)
    return response.data
  },

  async update(id: number, payload: SpecialtyPayload) {
    const response = await api.patch<ApiResponse<Specialty>>(`/specialties/${id}`, payload)
    return response.data
  },

  async remove(id: number) {
    const response = await api.delete<ApiResponse<[]>>(`/specialties/${id}`)
    return response.data
  },
}
