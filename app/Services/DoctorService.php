<?php

namespace App\Services;

use App\Constants\ActivityAction;
use App\Constants\Messages\DoctorMessage;
use App\Events\ActivityLogged;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class DoctorService
{
    private const int PER_PAGE = 10;

    public function getAll(): LengthAwarePaginator
    {
        return Doctor::query()
            ->with(['user', 'specialty'])
            ->latest()
            ->paginate(self::PER_PAGE);
    }

    public function create(array $data): Doctor
    {
        $this->isDoctor($data['user_id']);

        $doctor = Doctor::create($data);

        $loadedDoctor = $doctor->load(['user', 'specialty']);

        ActivityLogged::dispatch(
            ActivityAction::DOCTOR_CREATED,
            $loadedDoctor,
            Auth::user(),
            [
                'user_id' => $loadedDoctor->user_id,
                'specialty_id' => $loadedDoctor->specialty_id,
                'license_number' => $loadedDoctor->license_number,
            ]
        );

        return $loadedDoctor;
    }

    public function update(Doctor $doctor, array $data): Doctor
    {
        if (isset($data['user_id']) && $doctor->user_id !== $data['user_id']) {
            $this->isDoctor($data['user_id']);
        }

        $doctor->update($data);

        $loadedDoctor = $doctor->refresh()->load(['user', 'specialty']);

        ActivityLogged::dispatch(
            ActivityAction::DOCTOR_UPDATED,
            $loadedDoctor,
            Auth::user(),
            ['updated_fields' => array_keys($data)]
        );

        return $loadedDoctor;
    }

    public function delete(Doctor $doctor): void
    {
        ActivityLogged::dispatch(
            ActivityAction::DOCTOR_DELETED,
            $doctor,
            Auth::user(),
            [
                'user_id' => $doctor->user_id,
                'license_number' => $doctor->license_number,
            ]
        );

        $doctor->delete();
    }

    public function isDoctor(int $userId): void
    {
        $user = User::query()->with('role')->findOrFail($userId);

        if ($user->role?->name !== 'DOCTOR') {
            throw ValidationException::withMessages([
                'user_id' => DoctorMessage::USER_NOT_DOCTOR,
            ]);
        }
    }
}
