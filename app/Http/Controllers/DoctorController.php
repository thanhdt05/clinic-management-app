<?php

namespace App\Http\Controllers;

use App\Constants\Messages\DoctorMessage;
use App\Http\Requests\Doctor\StoreDoctorRequest;
use App\Http\Requests\Doctor\UpdateDoctorRequest;
use App\Http\Resources\DoctorResource;
use App\Models\Doctor;
use App\Services\DoctorService;
use App\Traits\HttpResponse;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class DoctorController extends Controller
{
    use HttpResponse;

    public function __construct(
        private readonly DoctorService $doctorService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $doctors = $this->doctorService->getAll();

        return $this->paginated(
            DoctorResource::collection($doctors),
            $doctors,
            DoctorMessage::DOCTOR_LIST_RETRIEVED
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDoctorRequest $request): JsonResponse
    {
        $doctor = $this->doctorService->create($request->validated());

        return $this->success(
            DoctorResource::make($doctor),
            DoctorMessage::DOCTOR_CREATED,
            Response::HTTP_CREATED
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(Doctor $doctor): JsonResponse
    {
        return $this->success(
            DoctorResource::make($doctor->load(['user', 'specialty'])),
            DoctorMessage::DOCTOR_DETAILS_RETRIEVED
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDoctorRequest $request, Doctor $doctor): JsonResponse
    {
        $doctor = $this->doctorService->update($doctor, $request->validated());

        return $this->success(
            DoctorResource::make($doctor),
            DoctorMessage::DOCTOR_UPDATED
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Doctor $doctor): JsonResponse
    {
        $this->doctorService->delete($doctor);

        return $this->success(
            [],
            DoctorMessage::DOCTOR_DELETED
        );
    }
}
