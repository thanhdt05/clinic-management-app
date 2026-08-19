import api from '@/services/api'

import type { ApiResponse } from '@/types/auth'

import type {
  Appointment,
  AppointmentQuery,
  AppointmentStatus,
  CreateAppointmentPayload,
  PaginatedAppointmentResponse,
  UpdateAppointmentPayload,
} from '@/types/appointment'

export const appointmentService = {
  async getAll(params: AppointmentQuery = {}): Promise<PaginatedAppointmentResponse> {
    const response = await api.get('/appointments', { params })
    return response.data
  },

  async getOne(id: number): Promise<ApiResponse<Appointment>> {
    const response = await api.get(`/appointments/${id}`)
    return response.data
  },

  async create(payload: CreateAppointmentPayload): Promise<ApiResponse<Appointment>> {
    const response = await api.post('/appointments', payload)
    return response.data
  },

  async update(id: number, payload: UpdateAppointmentPayload): Promise<ApiResponse<Appointment>> {
    const response = await api.patch(`/appointments/${id}`, payload)
    return response.data
  },

  async updateStatus(id: number, status: AppointmentStatus): Promise<ApiResponse<Appointment>> {
    const response = await api.patch(`/appointments/${id}/status`, { status })
    return response.data
  },
}
