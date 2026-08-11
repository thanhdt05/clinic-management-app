<?php

namespace App\Http\Controllers;

use App\Constants\Messages\SpecialtyMessage;
use App\Http\Requests\Specialty\StoreSpecialtyRequest;
use App\Http\Requests\Specialty\UpdateSpecialtyRequest;
use App\Http\Resources\SpecialtyResource;
use App\Models\Specialty;
use App\Services\SpecialtyService;
use App\Traits\HttpResponse;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class SpecialtyController extends Controller
{
    use HttpResponse;

    public function __construct(
        private readonly SpecialtyService $specialtyService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $specialties = $this->specialtyService->getAll();

        return $this->paginated(
            SpecialtyResource::collection($specialties),
            $specialties,
            SpecialtyMessage::SPECIALTY_LIST_RETRIEVED
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSpecialtyRequest $request): JsonResponse
    {
        $specialty = $this->specialtyService->create($request->validated());

        return $this->success(
            SpecialtyResource::make($specialty),
            SpecialtyMessage::SPECIALTY_CREATED,
            Response::HTTP_CREATED
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(Specialty $specialty): JsonResponse
    {
        return $this->success(
            SpecialtyResource::make($specialty),
            SpecialtyMessage::SPECIALTY_DETAILS_RETRIEVED
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSpecialtyRequest $request, Specialty $specialty): JsonResponse
    {
        $specialty = $this->specialtyService->update($specialty, $request->validated());

        return $this->success(
            SpecialtyResource::make($specialty),
            SpecialtyMessage::SPECIALTY_UPDATED
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Specialty $specialty): JsonResponse
    {
        $this->specialtyService->delete($specialty);

        return $this->success(
            [],
            SpecialtyMessage::SPECIALTY_DELETED
        );
    }
}
