<?php

namespace App\Http\Controllers;

use App\Constants\Messages\PatientMessage;
use App\Http\Requests\Patient\StorePatientRequest;
use App\Http\Requests\Patient\UpdatePatientRequest;
use App\Http\Resources\PatientResource;
use App\Models\Patient;
use App\Services\PatientService;
use App\Traits\HttpResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PatientController extends Controller
{
    use HttpResponse;

    public function __construct(
        private readonly PatientService $patientService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $patient = $this->patientService->getAll($request->all());

        return $this->paginated(
            PatientResource::collection($patient),
            $patient,
            PatientMessage::PATIENT_LIST_RETRIEVED
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
            PatientMessage::PATIENT_CREATED,
            Response::HTTP_CREATED
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(Patient $patient): JsonResponse
    {
        return $this->success(
            PatientResource::make($patient),
            PatientMessage::PATIENT_DETAILS_RETRIEVED
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
            PatientMessage::PATIENT_UPDATED
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
            PatientMessage::PATIENT_DELETED
        );
    }
}
