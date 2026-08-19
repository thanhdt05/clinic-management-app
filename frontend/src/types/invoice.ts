import type { ApiResponse } from '@/types/auth'

export type InvoiceStatus = 'unpaid' | 'paid' | 'cancelled'

export interface InvoicePatient {
  id: number
  code: string
  full_name: string
}

export interface InvoiceExamination {
  id: number
  patient: InvoicePatient
}

export interface Invoice {
  id: number
  invoice_code: string
  examination: InvoiceExamination

  subtotal: string
  discount: string
  total: string

  status: InvoiceStatus
  issued_at: string

  created_at: string
  updated_at: string
}

export interface InvoiceQuery {
  page?: number
  per_page?: number
  status?: InvoiceStatus
}

export interface CreateInvoicePayload {
  examination_id: number
  discount?: number
}

export interface UpdateInvoicePayload {
  discount: number
}

export interface PaginationMeta {
  current_page: number
  per_page: number
  total: number
  last_page: number
}

export interface PaginatedInvoiceResponse extends ApiResponse<Invoice[]> {
  meta: PaginationMeta
}
