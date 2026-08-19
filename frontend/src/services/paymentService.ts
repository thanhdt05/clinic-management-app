import api from '@/services/api'

import type { ApiResponse } from '@/types/auth'

import type {
  CreatePaymentPayload,
  CreatePaymentResponse,
  PaginatedPaymentResponse,
  Payment,
} from '@/types/payment'

export const paymentService = {
  async getByInvoice(invoiceId: number, page = 1): Promise<PaginatedPaymentResponse> {
    const response = await api.get(`/invoices/${invoiceId}/payments`, {
      params: {
        page,
      },
    })

    return response.data
  },

  async create(invoiceId: number, payload: CreatePaymentPayload): Promise<CreatePaymentResponse> {
    const response = await api.post(`/invoices/${invoiceId}/payments`, payload)

    return response.data
  },

  async capture(paymentId: number): Promise<ApiResponse<Payment>> {
    const response = await api.post(`/payments/${paymentId}/capture`)

    return response.data
  },
}
