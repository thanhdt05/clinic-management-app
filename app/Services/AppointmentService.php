<?php

namespace App\Services;

use App\Constants\ActivityAction;
use App\Constants\Messages\AppointmentMessage;
use App\Events\ActivityLogged;
use App\Models\Appointment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AppointmentService
{
    private const int PER_PAGE = 10;

    private const int DEFAULT_DURATION_MINUTES = 30;

    public function getAll(?User $user = null, array $filter = []): LengthAwarePaginator
    {
        $query = Appointment::query()
            ->with([
                'patient',
                'doctor.user',
                'doctor.specialty',
            ])
            ->latest('scheduled_at');

        if ($user?->doctor) {
            $query->where('doctor_id', $user->doctor->id);
        }

        if (isset($filter['doctor_id'])) {
            $query->where('doctor_id', $filter['doctor_id']);
        }

        if (isset($filter['patient_id'])) {
            $query->where('patient_id', $filter['patient_id']);
        }

        if (isset($filter['date'])) {
            $query->whereDate('scheduled_at', $filter['date']);
        }

        if (isset($filter['status'])) {
            $query->where('status', $filter['status']);
        }

        return $query->paginate($filter['per_page'] ?? self::PER_PAGE);
    }

    public function create(array $data): Appointment
    {
        $this->checkDoctorAvailable($data['doctor_id'], $data['scheduled_at']);

        $data['status'] = $data['status'] ?? 'scheduled';

        $appointment = Appointment::create($data);

        $loadedAppointment = $appointment->load([
            'patient',
            'doctor.user',
            'doctor.specialty',
        ]);

        ActivityLogged::dispatch(
            ActivityAction::APPOINTMENT_CREATED,
            $loadedAppointment,
            Auth::user(),
            [
                'doctor_id' => $loadedAppointment->doctor_id,
                'patient_id' => $loadedAppointment->patient_id,
                'scheduled_at' => $loadedAppointment->scheduled_at,
            ]
        );

        return $loadedAppointment;
    }

    public function getDetail(Appointment $appointment): Appointment
    {
        return $appointment->load([
            'patient',
            'doctor.user',
            'doctor.specialty',
        ]);
    }

    public function update(Appointment $appointment, array $data): Appointment
    {
        if ($appointment->status != 'scheduled') {
            throw ValidationException::withMessages([
                'status' => AppointmentMessage::CANNOT_UPDATE_NON_SCHEDULED,
            ]);
        }

        if (isset($data['scheduled_at'])) {
            $this->checkDoctorAvailable($appointment->doctor_id, $data['scheduled_at'], $appointment->id);
        }

        $appointment->update($data);

        $loadedAppointment = $appointment->refresh()->load([
            'patient',
            'doctor.user',
            'doctor.specialty',
        ]);

        ActivityLogged::dispatch(
            ActivityAction::APPOINTMENT_UPDATED,
            $loadedAppointment,
            Auth::user(),
            ['updated_fields' => array_keys($data)]
        );

        return $loadedAppointment;
    }

    public function updateStatus(Appointment $appointment, string $status): Appointment
    {
        $allowedTransitions = [
            'scheduled' => ['confirmed', 'cancelled'],
            'confirmed' => ['cancelled', 'completed'],
            'cancelled' => [],
            'completed' => [],
        ];

        if (! in_array($status, $allowedTransitions[$appointment->status] ?? [], true)) {
            throw ValidationException::withMessages([
                'status' => AppointmentMessage::INVALID_STATUS_TRANSITION,
            ]);
        }

        $oldStatus = $appointment->status;
        $appointment->update(['status' => $status]);

        $updatedAppointment = $appointment->refresh()->load([
            'patient',
            'doctor.user',
            'doctor.specialty',
        ]);

        ActivityLogged::dispatch(
            ActivityAction::APPOINTMENT_STATUS_CHANGED,
            $updatedAppointment,
            Auth::user(),
            ['old_status' => $oldStatus, 'new_status' => $status]
        );

        return $updatedAppointment;
    }

    public function checkDoctorAvailable(?int $doctorId, string $scheduledAt, ?int $currentAppointmentId = null): void
    {
        $newStart = Carbon::parse($scheduledAt);
        $newEnd = $newStart->copy()->addMinutes(self::DEFAULT_DURATION_MINUTES);
        $oldStart = $newStart->copy()->subMinutes(self::DEFAULT_DURATION_MINUTES);

        $hasConflict = Appointment::query()
            ->where('doctor_id', $doctorId)
            ->where('status', '!=', 'cancelled')
            ->where('scheduled_at', '<', $newEnd)
            ->where('scheduled_at', '>', $oldStart)
            ->when($currentAppointmentId,
                fn ($query) => $query->where('id', '!=', $currentAppointmentId)
            )
            ->exists();

        if ($hasConflict) {
            throw ValidationException::withMessages([
                'scheduled_at' => 'Doctor is not available at the selected time.',
            ]);
        }
    }
}
