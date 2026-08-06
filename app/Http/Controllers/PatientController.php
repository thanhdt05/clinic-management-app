<?php

namespace App\Http\Controllers;

use App\Http\Requests\Patient\StorePatientRequest;
use App\Http\Requests\Patient\UpdatePatientRequest;
use App\Http\Resources\PatientResource;
use App\Models\Patient;
use App\Services\PatientService;
use App\Traits\HttpResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatientController extends Controller
{

    use HttpResponse;

    public function __construct(
        private readonly PatientService $patientService
    ) {
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $patient = $this->patientService->getAll($request->all());

        return $this->paginated(
            PatientResource::collection($patient),
            $patient,
            'Patients list retrieved successfully.'
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePatientRequest $request): JsonResponse
    {
        $patient = $this->patientService->create($request->validated());

        return $this->success(
            PatientResource::make($patient),
            'Patient created successfully.',
            201
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(Patient $patient): JsonResponse
    {
        return $this->success(
            PatientResource::make($patient),
            'Patient details retrieved successfully.'
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePatientRequest $request, Patient $patient): JsonResponse
    {
        $patient = $this->patientService->update($patient, $request->validated());

        return $this->success(
            PatientResource::make($patient),
            'Patient updated successfully.'
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Patient $patient): JsonResponse
    {
        $this->patientService->delete($patient);

        return $this->success(
            [],
            'Patient deleted successfully.'
        );
    }
}
