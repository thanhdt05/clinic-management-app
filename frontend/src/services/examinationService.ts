import api from '@/services/api'

import type { ApiResponse } from '@/types/auth'

import type {
  CreateExaminationPayload,
  Examination,
  ExaminationQuery,
  PaginatedExaminationResponse,
  UpdateExaminationPayload,
} from '@/types/examination'

export const examinationService = {
  async getAll(params: ExaminationQuery = {}): Promise<PaginatedExaminationResponse> {
    const response = await api.get('/examinations', { params })
    return response.data
  },

  async getOne(id: number): Promise<ApiResponse<Examination>> {
    const response = await api.get(`/examinations/${id}`)
    return response.data
  },

  async create(payload: CreateExaminationPayload): Promise<ApiResponse<Examination>> {
    const response = await api.post('/examinations', payload)
    return response.data
  },

  async update(id: number, payload: UpdateExaminationPayload): Promise<ApiResponse<Examination>> {
    const response = await api.patch(`/examinations/${id}`, payload)
    return response.data
  },
}
