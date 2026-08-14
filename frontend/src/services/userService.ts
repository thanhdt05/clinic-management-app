import api from '@/services/api'

import type { ApiResponse, Role } from '@/types/auth'
import type { CreateUserPayload, UpdateUserPayload, PaginatedResponse, User } from '@/types/user'

export const userService = {
  async getAll(page = 1) {
    const response = await api.get<PaginatedResponse<User>>('/users', { params: { page } })
    return response.data
  },

  async getOne(id: number) {
    const response = await api.get<ApiResponse<User>>(`/users/${id}`)
    return response.data
  },

  async create(payload: CreateUserPayload) {
    const response = await api.post<ApiResponse<User>>('/users', payload)
    return response.data
  },

  async update(id: number, payload: UpdateUserPayload) {
    const response = await api.patch<ApiResponse<User>>(`/users/${id}`, payload)
    return response.data
  },

  async deactivate(id: number) {
    const response = await api.delete<ApiResponse<[]>>(`/users/${id}`)
    return response.data
  },

  async updateStatus(id: number, isActive: boolean) {
    const response = await api.patch<ApiResponse<User>>(`/users/${id}/status`, {
      is_active: isActive,
    })
    return response.data
  },

  async getRoles() {
    const response = await api.get<ApiResponse<Role[]>>('/roles')
    return response.data
  },
}
