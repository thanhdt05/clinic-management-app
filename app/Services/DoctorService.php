<?php

namespace App\Services;

use App\Models\Doctor;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
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

        return $doctor->load(['user', 'specialty']);
    }

    public function update(Doctor $doctor, array $data): Doctor
    {
        if (isset($data['user_id']) && $doctor->user_id !== $data['user_id']) {
            $this->isDoctor($data['user_id']);
        }

        $doctor->update($data);

        return $doctor->refresh()->load(['user', 'specialty']);
    }

    public function delete(Doctor $doctor): void
    {
        $doctor->delete();
    }

    public function isDoctor(int $userId): void
    {
        $user = User::query()->with('role')->findOrFail($userId);

        if ($user->role?->name !== 'DOCTOR') {
            throw ValidationException::withMessages([
                'user_id' => 'User không phải là bác sĩ',
            ]);
        }
    }
}