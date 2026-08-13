<?php

namespace App\Http\Controllers;

use App\Constants\Messages\InvoiceMessage;
use App\Http\Requests\Invoice\IndexInvoiceRequest;
use App\Http\Requests\Invoice\StoreInvoiceRequest;
use App\Http\Requests\Invoice\UpdateInvoiceRequest;
use App\Http\Requests\Invoice\UpdateInvoiceStatusRequest;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Services\InvoiceService;
use App\Traits\HttpResponse;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class InvoiceController extends Controller
{
    use HttpResponse;

    public function __construct(
        private readonly InvoiceService $invoiceService
    ) {}

    public function index(IndexInvoiceRequest $request): JsonResponse
    {
        $invoices = $this->invoiceService->getAll($request->validated());

        return $this->paginated(
            InvoiceResource::collection($invoices),
            $invoices,
            InvoiceMessage::INVOICE_LIST_RETRIEVED
        );
    }

    public function store(StoreInvoiceRequest $request): JsonResponse
    {
        $invoice = $this->invoiceService->create($request->validated());

        return $this->success(
            InvoiceResource::make($invoice),
            InvoiceMessage::INVOICE_CREATED,
            Response::HTTP_CREATED
        );
    }

    public function show(Invoice $invoice): JsonResponse
    {
        return $this->success(
            InvoiceResource::make(
                $this->invoiceService->getDetail($invoice)
            ),
            InvoiceMessage::INVOICE_DETAILS_RETRIEVED
        );
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice): JsonResponse
    {
        $invoice = $this->invoiceService->update(
            $invoice,
            $request->validated()
        );

        return $this->success(
            InvoiceResource::make($invoice),
            InvoiceMessage::INVOICE_UPDATED
        );
    }

    public function updateStatus(UpdateInvoiceStatusRequest $request, Invoice $invoice): JsonResponse
    {
        $invoice = $this->invoiceService->updateStatus(
            $invoice,
            $request->validated()['status']
        );

        return $this->success(
            InvoiceResource::make($invoice),
            InvoiceMessage::INVOICE_STATUS_UPDATED
        );
    }
}
