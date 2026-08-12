<?php

namespace App\Http\Controllers;

use App\Constants\Messages\PrescriptionMessage;
use App\Http\Requests\Prescription\StorePrescriptionRequest;
use App\Http\Resources\PrescriptionResource;
use App\Services\PrescriptionService;
use App\Traits\HttpResponse;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class PrescriptionController extends Controller
{
    use HttpResponse;

    public function __construct(
        private readonly PrescriptionService $prescriptionService,
    ) {}

    public function store(StorePrescriptionRequest $request): JsonResponse
    {
        $prescription = $this->prescriptionService->store(
            $request->user(),
            $request->validated(),
        );

        return $this->success(
            PrescriptionResource::make($prescription),
            PrescriptionMessage::PRESCRIPTION_CREATED,
            Response::HTTP_CREATED
        );
    }
}
