<?php

namespace App\Http\Controllers;

use App\Constants\Messages\PaymentMessage;
use App\Http\Requests\Payment\StorePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\PaymentService;
use App\Traits\HttpResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PaymentController extends Controller
{
    use HttpResponse;

    public function __construct(
        private readonly PaymentService $paymentService
    ) {}

    public function index(Request $request, Invoice $invoice): JsonResponse
    {
        $payments = $this->paymentService->getAll($invoice, $request->all());

        return $this->paginated(
            PaymentResource::collection($payments),
            $payments,
            PaymentMessage::PAYMENT_LIST_RETRIEVED
        );
    }

    public function store(StorePaymentRequest $request, Invoice $invoice): JsonResponse
    {
        $result = $this->paymentService->create($invoice, $request->validated());

        return $this->success(
            [
                'payment' => PaymentResource::make($result['payment']),
                'order_id' => $result['order_id'],
                'approval_url' => $result['approval_url'],
            ],
            PaymentMessage::PAYMENT_CREATED,
            Response::HTTP_CREATED
        );
    }

    public function capture(Payment $payment): JsonResponse
    {
        $updatedPayment = $this->paymentService->capture($payment);

        return $this->success(
            PaymentResource::make($updatedPayment),
            PaymentMessage::PAYMENT_CAPTURED
        );
    }
}
