import api from '@/services/api'

import type { ApiResponse } from '@/types/auth'

import type {
  AdjustStockPayload,
  CreateMedicinePayload,
  Medicine,
  MedicineQuery,
  PaginatedMedicineResponse,
  UpdateMedicinePayload,
} from '@/types/medicine'

export const medicineService = {
  async getAll(params: MedicineQuery = {}): Promise<PaginatedMedicineResponse> {
    const response = await api.get('/medicines', { params })

    return response.data
  },

  async getOne(id: number): Promise<ApiResponse<Medicine>> {
    const response = await api.get(`/medicines/${id}`)

    return response.data
  },

  async create(payload: CreateMedicinePayload): Promise<ApiResponse<Medicine>> {
    const response = await api.post('/medicines', payload)

    return response.data
  },

  async update(id: number, payload: UpdateMedicinePayload): Promise<ApiResponse<Medicine>> {
    const response = await api.put(`/medicines/${id}`, payload)

    return response.data
  },

  async remove(id: number): Promise<ApiResponse<void>> {
    const response = await api.delete(`/medicines/${id}`)

    return response.data
  },

  async adjustStock(id: number, payload: AdjustStockPayload): Promise<ApiResponse<Medicine>> {
    const response = await api.patch(`/medicines/${id}/stock`, payload)

    return response.data
  },
}
