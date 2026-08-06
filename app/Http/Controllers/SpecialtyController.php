<?php

namespace App\Http\Controllers;

use App\Http\Requests\Specialty\StoreSpecialtyRequest;
use App\Http\Requests\Specialty\UpdateSpecialtyRequest;
use App\Http\Resources\SpecialtyResource;
use App\Models\Specialty;
use App\Services\SpecialtyService;
use App\Traits\HttpResponse;
use Illuminate\Http\Request;

class SpecialtyController extends Controller
{
    use HttpResponse;

    public function __construct(
        private readonly SpecialtyService $specialtyService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $specialties = $this->specialtyService->getAll();

        return $this->paginated(
            SpecialtyResource::collection($specialties),
            $specialties,
            'Specialty list retrieved successfully.'
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSpecialtyRequest $request)
    {
        $specialty = $this->specialtyService->create($request->validated());

        return $this->success(
            SpecialtyResource::make($specialty),
            'Specialty created successfully.',
            201
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(Specialty $specialty)
    {
        return $this->success(
            SpecialtyResource::make($specialty),
            'Specialty details retrieved successfully.'
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSpecialtyRequest $request, Specialty $specialty)
    {
        $specialty = $this->specialtyService->update($specialty, $request->validated());

        return $this->success(
            SpecialtyResource::make($specialty),
            'Specialty updated successfully.',
            200
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Specialty $specialty)
    {
        $this->specialtyService->delete($specialty);

        return $this->success(
            [],
            'Specialty deleted successfully.',
            200
        );
    }
}
