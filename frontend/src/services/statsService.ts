import api from '@/services/api'
import type { ApiResponse } from '@/types/auth'
import type { DashboardStats } from '@/types/stats'

export const statsService = {
  async getDashboardStats(): Promise<ApiResponse<DashboardStats>> {
    const response = await api.get('/stats')
    return response.data
  },
}
