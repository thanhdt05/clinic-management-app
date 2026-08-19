import type { ApiResponse } from '@/types/auth'

export type PaymentMethod = 'paypal' | 'visa'

export type PaymentStatus = 'pending' | 'completed' | 'failed' | 'cancelled'

export interface PaymentInvoice {
  id: number
  invoice_code: string
  total: string
  status: string
}

export interface Payment {
  id: number
  invoice_id: number

  amount: string
  method: PaymentMethod
  status: PaymentStatus

  provider: string
  provider_order_id: string | null
  provider_capture_id: string | null

  paid_at: string | null
  note: string | null

  invoice?: PaymentInvoice

  created_at: string
  updated_at: string
}

export interface CreatePaymentPayload {
  amount: number
  method: PaymentMethod
  note?: string | null
}

export interface CreatePaymentData {
  payment: Payment
  order_id: string
  approval_url: string
}

export interface PaginationMeta {
  current_page: number
  per_page: number
  total: number
  last_page: number
}

export interface PaginatedPaymentResponse extends ApiResponse<Payment[]> {
  meta: PaginationMeta
}

export type CreatePaymentResponse = ApiResponse<CreatePaymentData>
