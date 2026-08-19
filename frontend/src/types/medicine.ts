import type { ApiResponse } from '@/types/auth'

export type MedicineStockStatus = 'in_stock' | 'out_of_stock'

export interface Medicine {
  id: number
  code: string
  name: string
  unit: string

  price: string

  stock: number
  is_active: boolean

  created_at: string | null
  updated_at: string | null
}

export interface CreateMedicinePayload {
  code: string
  name: string
  unit: string
  price: number
  stock: number
  is_active: boolean
}

export interface UpdateMedicinePayload {
  code: string
  name: string
  unit: string
  price: number
  is_active: boolean
}

export interface AdjustStockPayload {
  quantity: number
}

export interface MedicineQuery {
  page?: number
  per_page?: number
  stock_status?: MedicineStockStatus
}

export interface PaginationMeta {
  current_page: number
  per_page: number
  total: number
  last_page: number
}

export interface PaginatedMedicineResponse extends ApiResponse<Medicine[]> {
  meta: PaginationMeta
}
