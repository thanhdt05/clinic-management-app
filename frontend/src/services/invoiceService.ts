import api from '@/services/api'

import type { ApiResponse } from '@/types/auth'

import type {
  CreateInvoicePayload,
  Invoice,
  InvoiceQuery,
  PaginatedInvoiceResponse,
  UpdateInvoicePayload,
} from '@/types/invoice'

export const invoiceService = {
  async getAll(params: InvoiceQuery = {}): Promise<PaginatedInvoiceResponse> {
    const response = await api.get('/invoices', {
      params,
    })

    return response.data
  },

  async getOne(id: number): Promise<ApiResponse<Invoice>> {
    const response = await api.get(`/invoices/${id}`)

    return response.data
  },

  async create(payload: CreateInvoicePayload): Promise<ApiResponse<Invoice>> {
    const response = await api.post('/invoices', payload)

    return response.data
  },

  async update(id: number, payload: UpdateInvoicePayload): Promise<ApiResponse<Invoice>> {
    const response = await api.patch(`/invoices/${id}`, payload)

    return response.data
  },

  async cancel(id: number): Promise<ApiResponse<Invoice>> {
    const response = await api.patch(`/invoices/${id}/status`, {
      status: 'cancelled',
    })

    return response.data
  },
}
