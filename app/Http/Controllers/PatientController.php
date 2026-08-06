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
            'Lấy danh sách bệnh nhân thành công.'
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
            'Thêm bệnh nhân thành công.',
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
            'Lấy thông tin bệnh nhân thành công.'
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
            'Cập nhật thông tin bệnh nhân thành công.'
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
            'Xóa thông tin bệnh nhân thành công.'
        );
    }
}
