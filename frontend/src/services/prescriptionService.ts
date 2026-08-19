import api from '@/services/api'

import type { ApiResponse } from '@/types/auth'

import type {
  AddPrescriptionItemPayload,
  CreatePrescriptionPayload,
  PaginatedPrescriptionResponse,
  Prescription,
  PrescriptionQuery,
  UpdatePrescriptionItemPayload,
  UpdatePrescriptionPayload,
} from '@/types/prescription'

export const prescriptionService = {
  async getAll(params: PrescriptionQuery = {}): Promise<PaginatedPrescriptionResponse> {
    const response = await api.get('/prescriptions', { params })
    return response.data
  },

  async getOne(id: number): Promise<ApiResponse<Prescription>> {
    const response = await api.get(`/prescriptions/${id}`)
    return response.data
  },

  async create(payload: CreatePrescriptionPayload): Promise<ApiResponse<Prescription>> {
    const response = await api.post('/prescriptions', payload)
    return response.data
  },

  async update(id: number, payload: UpdatePrescriptionPayload): Promise<ApiResponse<Prescription>> {
    const response = await api.patch(`/prescriptions/${id}`, payload)
    return response.data
  },

  async addItem(
    id: number,
    payload: AddPrescriptionItemPayload,
  ): Promise<ApiResponse<Prescription>> {
    const response = await api.post(`/prescriptions/${id}/items`, payload)
    return response.data
  },

  async updateItem(
    id: number,
    itemId: number,
    payload: UpdatePrescriptionItemPayload,
  ): Promise<ApiResponse<Prescription>> {
    const response = await api.patch(`/prescriptions/${id}/items/${itemId}`, payload)
    return response.data
  },

  async removeItem(id: number, itemId: number): Promise<ApiResponse<Prescription>> {
    const response = await api.delete(`/prescriptions/${id}/items/${itemId}`)
    return response.data
  },
}
