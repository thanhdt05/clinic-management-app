<?php

namespace App\Services;

use App\Constants\ActivityAction;
use App\Constants\Messages\InvoiceMessage;
use App\Events\ActivityLogged;
use App\Models\Examination;
use App\Models\Invoice;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class InvoiceService
{
    private const int PER_PAGE = 10;

    public function getAll(array $filter): LengthAwarePaginator
    {
        $query = Invoice::query()
            ->with(['examination.patient'])
            ->latest('issued_at');

        if (isset($filter['status'])) {
            $query->where('status', $filter['status']);
        }

        return $query->paginate($filter['per_page'] ?? self::PER_PAGE);
    }

    public function create(array $data): Invoice
    {
        $examination = Examination::with([
            'patient',
            'prescription.items.medicine',
        ])
            ->findOrFail($data['examination_id']);

        if ($examination->invoice()->exists()) {
            throw ValidationException::withMessages([
                'examination_id' => InvoiceMessage::EXAMINATION_ALREADY_HAS_INVOICE,
            ]);
        }

        $medicineTotal = $this->calculateMedicineTotal($examination);

        $consultationFee = (float) config('clinic.examination_fee');

        $subtotal = $medicineTotal + $consultationFee;
        $discount = (float) ($data['discount'] ?? 0);

        if ($discount > $subtotal) {
            throw ValidationException::withMessages([
                'discount' => InvoiceMessage::DISCOUNT_EXCEEDS_SUBTOTAL,
            ]);
        }

        $total = $subtotal - $discount;

        $invoice = Invoice::create([
            'examination_id' => $examination->id,
            'invoice_code' => $this->generateInvoiceCode(),
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => $total,
            'status' => 'unpaid',
            'issued_at' => now(),
        ]);

        $loadedInvoice = $invoice->load([
            'examination.patient',
            'examination.prescription.items.medicine',
        ]);

        ActivityLogged::dispatch(
            ActivityAction::INVOICE_CREATED,
            $loadedInvoice,
            Auth::user(),
            ['invoice_code' => $loadedInvoice->invoice_code, 'total' => $loadedInvoice->total]
        );

        return $loadedInvoice;
    }

    public function getDetail(Invoice $invoice): Invoice
    {
        return $invoice->load([
            'examination.patient',
            'examination.doctor.user',
            'examination.prescription.items.medicine',
        ]);
    }

    public function update(Invoice $invoice, array $data): Invoice
    {
        $this->ensureInvoiceEditable($invoice);

        $discount = (float) $data['discount'];
        $subtotal = (float) $invoice->subtotal;

        if ($discount > $subtotal) {
            throw ValidationException::withMessages([
                'discount' => InvoiceMessage::DISCOUNT_EXCEEDS_SUBTOTAL,
            ]);
        }

        $total = $subtotal - $discount;

        $invoice->update([
            'discount' => $discount,
            'total' => $total,
        ]);

        $loadedInvoice = $invoice->refresh()->load([
            'examination.patient',
            'examination.doctor.user',
            'examination.prescription.items.medicine',
        ]);

        ActivityLogged::dispatch(
            ActivityAction::INVOICE_UPDATED,
            $loadedInvoice,
            Auth::user(),
            [
                'invoice_code' => $loadedInvoice->invoice_code,
                'discount' => $loadedInvoice->discount,
                'total' => $loadedInvoice->total,
            ]
        );

        return $loadedInvoice;
    }

    public function updateStatus(Invoice $invoice, string $status): Invoice
    {
        $this->ensureInvoiceEditable($invoice);

        $oldStatus = $invoice->status;

        $invoice->update([
            'status' => $status,
        ]);

        $loadedInvoice = $invoice->refresh()->load([
            'examination.patient',
            'examination.doctor.user',
            'examination.prescription.items.medicine',
        ]);

        ActivityLogged::dispatch(
            ActivityAction::INVOICE_STATUS_CHANGED,
            $loadedInvoice,
            Auth::user(),
            [
                'invoice_code' => $loadedInvoice->invoice_code,
                'old_status' => $oldStatus,
                'new_status' => $loadedInvoice->status,
            ]
        );

        return $loadedInvoice;
    }

    private function calculateMedicineTotal(Examination $examination): float
    {
        if (! $examination->prescription) {
            return 0;
        }

        $total = (float) $examination->prescription->items
            ->sum(fn ($item) => $item->quantity * (float) $item->medicine->price);

        return $total;
    }

    private function generateInvoiceCode(): string
    {
        $year = date('Y');
        $lastInvoice = Invoice::where('invoice_code', 'like', "$year-%")
            ->orderBy('invoice_code', 'desc')
            ->first();

        $number = 1;
        if ($lastInvoice) {
            $lastNumber = (int) substr($lastInvoice->invoice_code, 5);
            $number = $lastNumber + 1;
        }

        return $year.'-'.str_pad($number, 5, '0', STR_PAD_LEFT);
    }

    private function ensureInvoiceEditable(Invoice $invoice): void
    {
        if ($invoice->status !== 'unpaid') {
            throw ValidationException::withMessages([
                'status' => InvoiceMessage::ONLY_UNPAID_CAN_BE_MODIFIED,
            ]);
        }

        if (method_exists($invoice, 'payments') && $invoice->payments()->where('status', 'completed')->exists()) {
            throw ValidationException::withMessages([
                'status' => InvoiceMessage::INVOICE_HAS_COMPLETED_PAYMENTS,
            ]);
        }
    }
}
