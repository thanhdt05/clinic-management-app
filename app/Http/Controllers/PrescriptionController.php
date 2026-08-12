<?php

namespace App\Http\Controllers;

use App\Constants\Messages\PrescriptionMessage;
use App\Http\Requests\Prescription\AddPrescriptionItemRequest;
use App\Http\Requests\Prescription\IndexPrescriptionRequest;
use App\Http\Requests\Prescription\StorePrescriptionRequest;
use App\Http\Requests\Prescription\UpdatePrescriptionItemRequest;
use App\Http\Resources\PrescriptionResource;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Services\PrescriptionService;
use App\Traits\HttpResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PrescriptionController extends Controller
{
    use HttpResponse;

    public function __construct(
        private readonly PrescriptionService $prescriptionService,
    ) {}

    public function index(IndexPrescriptionRequest $request): JsonResponse
    {
        $prescriptions = $this->prescriptionService->getAll(
            $request->user(),
            $request->validated(),
        );

        return $this->paginated(
            PrescriptionResource::collection($prescriptions),
            $prescriptions,
            PrescriptionMessage::PRESCRIPTION_LIST_RETRIEVED,
        );
    }

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

    public function addItem(AddPrescriptionItemRequest $request, Prescription $prescription): JsonResponse
    {
        $prescription = $this->prescriptionService->addItem(
            $request->user(),
            $prescription,
            $request->validated(),
        );

        return $this->success(
            PrescriptionResource::make($prescription),
            PrescriptionMessage::PRESCRIPTION_ITEM_ADDED,
            Response::HTTP_CREATED
        );
    }

    public function updateItem(UpdatePrescriptionItemRequest $request, Prescription $prescription, PrescriptionItem $prescriptionItem): JsonResponse
    {
        $prescription = $this->prescriptionService->updateItem(
            $request->user(),
            $prescription,
            $prescriptionItem,
            $request->validated(),
        );

        return $this->success(
            PrescriptionResource::make($prescription),
            PrescriptionMessage::PRESCRIPTION_ITEM_UPDATED,
        );
    }

    public function removeItem(Request $request, Prescription $prescription, PrescriptionItem $prescriptionItem): JsonResponse
    {
        $prescription = $this->prescriptionService->removeItem(
            $request->user(),
            $prescription,
            $prescriptionItem,
        );

        return $this->success(
            PrescriptionResource::make($prescription),
            PrescriptionMessage::PRESCRIPTION_ITEM_REMOVED,
        );
    }
}
