<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\Examination;
use App\Models\Invoice;
use App\Models\Medicine;
use App\Models\Patient;
use App\Models\PrescriptionItem;

class StatsService
{
    private const int DEFAULT_LOW_STOCK_THRESHOLD = 10;

     public function getAdminDashboardStats(): array
    {
        return [
            'overview' => $this->getOverviewKpis(),
            'revenue_stream' => $this->getRevenueStream(6),
            'top_prescribed_medicines' => $this->getTopPrescribedMedicines(5),
            'top_doctors' => $this->getTopDoctors(5),
            'recent_invoices' => $this->getRecentInvoices(5),
            'recent_activities' => $this->getRecentActivities(10),
        ];
    }

    // Top 4 KPI
    public function getOverviewKpis(): array
    {
        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();

        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $totalPatients = Patient::query()->count();

        $todayAppointments = Appointment::query()
            ->whereBetween('scheduled_at', [$todayStart, $todayEnd])
            ->count();

        $monthlyRevenue = (float) Invoice::query()
            ->where('status', 'paid')
            ->whereBetween('issued_at', [$monthStart, $monthEnd])
            ->sum('total');

        $lowStockMedicines = Medicine::query()
            ->where('is_active', true)
            ->where('stock', '<=', self::DEFAULT_LOW_STOCK_THRESHOLD)
            ->count();

        return [
            'total_patients' => $totalPatients,
            'today_appointments' => $todayAppointments,
            'monthly_revenue' => $monthlyRevenue,
            'low_stock_medicines' => $lowStockMedicines,
        ];
    }

    // Revenue in 6 months
    public function getRevenueStream(int $months = 6): array
    {
        $startDate = now()->subMonths($months - 1)->startOfMonth();

        return Invoice::query()
            ->selectRaw("TO_CHAR(issued_at, 'YYYY-MM') as month, SUM(total) as revenue, SUM(discount) as discount")
            ->where('status', 'paid')
            ->where('issued_at', '>=', $startDate)
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(fn($item) => [
                'month' => (string) $item->month,
                'revenue' => (float) $item->revenue,
                'discount' => (float) $item->discount,
            ])
            ->toArray();
    }

    // Top 5 doctors
    public function getTopDoctors(int $limit = 5): array
    {
        return Examination::query()
            ->join('doctors', 'examinations.doctor_id', '=', 'doctors.id')
            ->join('users', 'doctors.user_id', '=', 'users.id')
            ->selectRaw('doctors.id, users.name as doctor_name, COUNT(examinations.id) as examination_count')
            ->whereBetween('examinations.created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->groupBy('doctors.id', 'users.name')
            ->orderByDesc('examination_count')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    // Top 5 prescribed medicines
    public function getTopPrescribedMedicines(int $limit = 5): array
    {
        return PrescriptionItem::query()
            ->join('medicines', 'prescription_items.medicine_id', '=', 'medicines.id')
            ->selectRaw('medicines.id, medicines.name, medicines.unit, CAST(SUM(prescription_items.quantity) AS INTEGER) as total_prescribed')
            ->groupBy('medicines.id', 'medicines.name', 'medicines.unit')
            ->orderByDesc('total_prescribed')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    // Top 5 newest invoice
    public function getRecentInvoices(int $limit = 5): array
    {
        return Invoice::query()
            ->with(['examination.patient:id,full_name,code'])
            ->latest('issued_at')
            ->limit($limit)
            ->get(['id', 'examination_id', 'invoice_code', 'total', 'status', 'issued_at'])
            ->toArray();
    }

    // 10 recent activity logs
    public function getRecentActivities(int $limit = 10): array
    {
        return ActivityLog::query()
            ->with(['user:id,name'])
            ->latest()
            ->limit($limit)
            ->get()
            ->toArray();
    }
}