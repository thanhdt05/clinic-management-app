<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class AppointmentService
{
    private const int PER_PAGE = 10;

    public function getAll(?User $user = null, array $filter = []): LengthAwarePaginator
    {
        $query = Appointment::query()
                        ->with([
                            'patient',
                            'doctor.user',
                            'doctor.specialty'
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
        $appointment = Appointment::create($data);
        return $appointment->load([
            'patient',
            'doctor.user',
            'doctor.specialty'
        ]);
    }

    public function getDetail(Appointment $appointment): Appointment
    {
        return $appointment->load([
            'patient',
            'doctor.user',
            'doctor.specialty'
        ]);
    }

    public function update(Appointment $appointment, array $data): Appointment
    {
        if ($appointment->status != 'scheduled') {
            throw ValidationException::withMessages([
                'status' => 'Appointment has already been confirmed, cancelled, or completed.',
            ]);
        }

        $appointment->update($data);

        return $appointment->load([
            'patient',
            'doctor.user',
            'doctor.specialty'
        ]);
    }

    public function updateStatus(Appointment $appointment, string $status)
    {
        $allowedTransitions = [
            'scheduled' => ['confirmed', 'cancelled'],
            'confirmed' => ['cancelled', 'completed'],
            'cancelled' => [],
            'completed' => [],
        ];

        if (!in_array($status, $allowedTransitions[$appointment->status] ?? [], true)) {
            throw ValidationException::withMessages([
                'status' => 'Invalid status transition for this appointment.',
            ]);
        }

        $appointment->update(['status' => $status]);

        return $appointment->refresh()->load([
            'patient',
            'doctor.user',
            'doctor.specialty'
        ]);
    }
}