import type { ApiResponse } from '@/types/auth'

export interface Specialty {
  id: number
  name: string
  description: string | null
  created_at: string | null
  updated_at: string | null
}

export interface SpecialtyPayload {
  name: string
  description: string | null
}

export interface PaginationMeta {
  current_page: number
  per_page: number
  total: number
  last_page: number
}

export interface PaginatedSpecialtyResponse extends ApiResponse<Specialty[]> {
  meta: PaginationMeta
}
