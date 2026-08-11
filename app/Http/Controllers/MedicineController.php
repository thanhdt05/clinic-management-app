<?php

namespace App\Http\Controllers;

use App\Constants\Messages\MedicineMessage;
use App\Http\Requests\Medicine\AdjustMedicineStockRequest;
use App\Http\Requests\Medicine\IndexMedicineRequest;
use App\Http\Requests\Medicine\StoreMedicineRequest;
use App\Http\Requests\Medicine\UpdateMedicineRequest;
use App\Http\Resources\MedicineResource;
use App\Models\Medicine;
use App\Services\MedicineService;
use App\Traits\HttpResponse;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class MedicineController extends Controller
{
    use HttpResponse;

    public function __construct(
        private readonly MedicineService $medicineService
    ) {}

    public function index(IndexMedicineRequest $request): JsonResponse
    {
        $medicines = $this->medicineService->getAll(
            $request->validated()
        );

        return $this->paginated(
            MedicineResource::collection($medicines),
            $medicines,
            MedicineMessage::MEDICINE_LIST_RETRIEVED
        );
    }

    public function store(StoreMedicineRequest $request): JsonResponse
    {
        $medicine = $this->medicineService->create($request->validated());

        return $this->success(
            MedicineResource::make($medicine),
            MedicineMessage::MEDICINE_CREATED,
            Response::HTTP_CREATED
        );
    }

    public function show(Medicine $medicine): JsonResponse
    {
        return $this->success(
            MedicineResource::make($medicine),
            MedicineMessage::MEDICINE_DETAILS_RETRIEVED
        );
    }

    public function update(UpdateMedicineRequest $request, Medicine $medicine): JsonResponse
    {
        $medicine = $this->medicineService->update($medicine, $request->validated());

        return $this->success(
            MedicineResource::make($medicine),
            MedicineMessage::MEDICINE_UPDATED
        );
    }

    public function destroy(Medicine $medicine): JsonResponse
    {
        $this->medicineService->delete($medicine);

        return $this->success(
            null,
            MedicineMessage::MEDICINE_DELETED
        );
    }

    public function adjustStock(AdjustMedicineStockRequest $request, Medicine $medicine): JsonResponse
    {
        $medicine = $this->medicineService->adjustStock($medicine, $request->validated());

        return $this->success(
            MedicineResource::make($medicine),
            MedicineMessage::MEDICINE_STOCK_ADJUSTED
        );
    }
}
