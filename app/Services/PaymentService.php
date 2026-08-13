<?php

namespace App\Services;

use App\Constants\Messages\PaymentMessage;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class PaymentService
{
    private const int PER_PAGE = 10;

    public function __construct(
        private readonly PayPalService $paypalService
    ) {}

    public function getAll(array $data): LengthAwarePaginator
    {
        $query = Payment::query()->latest();

        return $query->paginate($data['per_page'] ?? self::PER_PAGE);
    }

    public function create(Invoice $invoice, array $data)
    {
        if ($invoice->status !== 'unpaid') {
            throw ValidationException::withMessages([
                'invoice' => PaymentMessage::INVOICE_MUST_BE_UNPAID_CREATE,
            ]);
        }

        $remainingAmount = $this->getRemainingAmount($invoice);
        $amount = round((float) $data['amount'], 2);

        if ($amount > $remainingAmount) {
            throw ValidationException::withMessages([
                'amount' => PaymentMessage::AMOUNT_EXCEEDS_REMAINING,
            ]);
        }

        $paypalOrder = $this->paypalService->createOrder($amount);

        if (empty($paypalOrder['order_id'])) {
            throw ValidationException::withMessages([
                'payment' => PaymentMessage::FAILED_TO_CREATE_PAYPAL_ORDER,
            ]);
        }

        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'amount' => $amount,
            'method' => $data['method'],
            'status' => 'pending',
            'provider' => 'paypal',
            'provider_order_id' => $paypalOrder['order_id'],
            'provider_capture_id' => null,
            'paid_at' => null,
            'note' => $data['note'] ?? null,
        ]);

        return [
            'payment' => $payment,
            'order_id' => $paypalOrder['order_id'],
            'approval_url' => $paypalOrder['approval_url'],
        ];
    }

    public function capture(Payment $payment)
    {
        $this->ensurePaymentCanBeCaptured($payment);

        try {
            $captureResult = $this->paypalService->captureOrder(
                $payment->provider_order_id
            );
        } catch (RuntimeException $exception) {
            $payment->update([
                'status' => 'failed',
            ]);

            throw ValidationException::withMessages([
                'payment' => PaymentMessage::FAILED_TO_CAPTURE_PAYPAL_PAYMENT,
            ]);
        }

        return DB::transaction(function () use ($payment, $captureResult) {
            $lockedPayment = Payment::query()
                ->lockForUpdate()
                ->findOrFail($payment->id);

            if ($lockedPayment->status !== 'pending') {
                throw ValidationException::withMessages([
                    'payment' => PaymentMessage::ONLY_PENDING_CAN_BE_CAPTURED,
                ]);
            }

            $invoice = Invoice::query()
                ->lockForUpdate()
                ->findOrFail($payment->invoice_id);

            $lockedPayment->update([
                'status' => 'completed',
                'provider_capture_id' => $captureResult['capture_id'],
                'paid_at' => now(),
            ]);

            $paidInvoiceAmount = (float) Payment::query()
                ->where('invoice_id', $invoice->id)
                ->where('status', 'completed')
                ->sum('amount');

            if (round($paidInvoiceAmount, 2) >= round((float) $invoice->total, 2)) {
                $invoice->update([
                    'status' => 'paid',
                ]);
            }

            return $lockedPayment->refresh()->load('invoice');
        });
    }

    public function getRemainingAmount(Invoice $invoice): float
    {
        $paidAmount = (float) $invoice->payments()
            ->whereIn('status', ['completed', 'pending'])
            ->sum('amount');

        return max(round((float) $invoice->total - $paidAmount, 2), 0.00);
    }

    public function ensurePaymentCanBeCaptured(Payment $payment): void
    {
        if ($payment->status !== 'pending') {
            throw ValidationException::withMessages([
                'payment' => PaymentMessage::ONLY_PENDING_CAN_BE_CAPTURED,
            ]);
        }

        if ($payment->invoice->status !== 'unpaid') {
            throw ValidationException::withMessages([
                'invoice' => PaymentMessage::INVOICE_MUST_BE_UNPAID_CAPTURE,
            ]);
        }

        if (! $payment->provider_order_id) {
            throw ValidationException::withMessages([
                'payment' => PaymentMessage::MISSING_PAYPAL_ORDER_ID,
            ]);
        }
    }
}
