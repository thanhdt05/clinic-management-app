<?php

namespace App\Http\Controllers;

use App\Constants\Messages\AppointmentMessage;
use App\Http\Requests\Appointment\IndexAppointmentRequest;
use App\Http\Requests\Appointment\StoreAppointmentRequest;
use App\Http\Requests\Appointment\UpdateAppointmentRequest;
use App\Http\Requests\Appointment\UpdateAppointmentStatusRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Services\AppointmentService;
use App\Traits\HttpResponse;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

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
            AppointmentMessage::APPOINTMENT_LIST_RETRIEVED
        );
    }

    public function store(StoreAppointmentRequest $request): JsonResponse
    {
        $appointment = $this->appointmentService->create($request->validated());

        return $this->success(
            AppointmentResource::make($appointment),
            AppointmentMessage::APPOINTMENT_CREATED,
            Response::HTTP_CREATED
        );
    }

    public function show(Appointment $appointment): JsonResponse
    {
        $appointment = $this->appointmentService->getDetail($appointment);

        return $this->success(
            AppointmentResource::make($appointment),
            AppointmentMessage::APPOINTMENT_DETAILS_RETRIEVED
        );
    }

    public function update(UpdateAppointmentRequest $request, Appointment $appointment): JsonResponse
    {
        $appointment = $this->appointmentService->update($appointment, $request->validated());

        return $this->success(
            AppointmentResource::make($appointment),
            AppointmentMessage::APPOINTMENT_UPDATED
        );
    }

    public function updateStatus(UpdateAppointmentStatusRequest $request, Appointment $appointment): JsonResponse
    {
        $appointment = $this->appointmentService->updateStatus($appointment, $request->validated('status'));

        return $this->success(
            AppointmentResource::make($appointment),
            AppointmentMessage::APPOINTMENT_STATUS_UPDATED
        );
    }
}
