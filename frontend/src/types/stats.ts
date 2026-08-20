export interface StatsOverview {
  total_patients: number
  today_appointments: number
  monthly_revenue: number
  low_stock_medicines: number
}

export interface RevenueStreamItem {
  month: string
  revenue: number
  discount: number
}

export interface TopPrescribedMedicine {
  id: number
  name: string
  unit: string
  total_prescribed: number
}

export interface TopDoctor {
  id: number
  doctor_name: string
  examination_count: number
}

export interface RecentInvoice {
  id: number
  examination_id: number
  invoice_code: string
  total: string | number
  status: 'paid' | 'unpaid' | 'cancelled'
  issued_at: string
  examination?: {
    id: number
    patient?: {
      id: number
      full_name: string
      code: string
    }
  }
}

export interface RecentActivity {
  id: number
  user_id: number
  action: string
  subject_type: string
  subject_id: number
  meta?: Record<string, any>
  created_at: string
  user?: {
    id: number
    name: string
  }
}

export interface DashboardStats {
  overview: StatsOverview
  revenue_stream: RevenueStreamItem[]
  top_prescribed_medicines: TopPrescribedMedicine[]
  top_doctors: TopDoctor[]
  recent_invoices: RecentInvoice[]
  recent_activities: RecentActivity[]
}
