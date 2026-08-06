<?php

namespace App\Http\Controllers;

use App\Http\Requests\Appointment\IndexAppointmentRequest;
use App\Http\Requests\Appointment\StoreAppointmentRequest;
use App\Http\Requests\Appointment\UpdateAppointmentRequest;
use App\Http\Requests\Appointment\UpdateAppointmentStatusRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Services\AppointmentService;
use App\Traits\HttpResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    use HttpResponse;

    public function __construct(
        private readonly AppointmentService $appointmentService
    ) {}

    public function index(IndexAppointmentRequest $request): JsonResponse
    {
        $appointments = $this->appointmentService->getAll($request->user(), $request->validated());

        return $this->paginated(
            AppointmentResource::collection($appointments),
            $appointments,
            'Appointment list retrieved successfully.'
        );
    }

    public function store(StoreAppointmentRequest $request): JsonResponse
    {
        $appointment = $this->appointmentService->create($request->validated());

        return $this->success(
            AppointmentResource::make($appointment),
            'Appointment created successfully.',
            201
        );
    }

    public function show(Appointment $appointment): JsonResponse
    {
        $appointment = $this->appointmentService->getDetail($appointment);

        return $this->success(
            AppointmentResource::make($appointment),
            'Appointment details retrieved successfully.'
        );
    }

    public function update(UpdateAppointmentRequest $request, Appointment $appointment): JsonResponse
    {
        $appointment = $this->appointmentService->update($appointment, $request->validated());

        return $this->success(
            AppointmentResource::make($appointment),
            'Appointment updated successfully.'
        );
    }

    public function updateStatus(UpdateAppointmentStatusRequest $request, Appointment $appointment): JsonResponse
    {
        $appointment = $this->appointmentService->updateStatus($appointment, $request->validated('status'));

        return $this->success(
            AppointmentResource::make($appointment),
            'Appointment status updated successfully.'
        );
    }
}
