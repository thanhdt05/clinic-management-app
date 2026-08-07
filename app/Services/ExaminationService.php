<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Examination;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExaminationService
{
    private const int PER_PAGE = 10;

    // Doctor can only view their own patient's exam, 
    // Cashier and admin can view everyone examination
    public function getAll(User $user, array $filter = []){
        $query = Examination::query()
                    ->with( 'doctor.user',
                            'doctor.specialty',
                            'patient',
                            'appointment')
                    ->latest('examined_at');

        if ($user->doctor) {
            $query->where('doctor_id', $user->doctor->id);
        }

        if (isset($filter['doctor_id'])){
            $query->where('doctor_id', $filter['doctor_id']);
        }
        if (isset($filter['patient_id'])){
            $query->where('patient_id', $filter['patient_id']);
        }

        return $query->paginate($filter['per_page'] ?? self::PER_PAGE);
    }
    public function create(User $user, array $data)
    {
        return DB::transaction(function() use ($user, $data){
            $appointment = Appointment::query()
                ->lockForUpdate()
                ->findOrFail($data['appointment_id']);

            if ($appointment->status !== 'confirmed')
            {
                throw ValidationException::withMessages([
                    'appointment_id' => 'Only confirmed appointment can create examination'
                ]);
            }

            $isDoctor = $user->doctor && $user->doctor->id === $appointment->doctor_id;
            $isAdmin = $user->role?->name === 'ADMIN';

            if (!$isDoctor && !$isAdmin) {
                throw ValidationException::withMessages([
                    'doctor_id' => 'You are not authorized to examine this appointment',
                ]);
            }

            $examination = Examination::create([
                'appointment_id' => $appointment->id,
                'doctor_id' => $appointment->doctor_id,
                'patient_id' => $appointment->patient_id,
                'diagnosis' => $data['diagnosis'],
                'notes' => $data['notes'] ?? null,
                'examined_at' => now(),
            ]);

            $appointment->update([
                'status' => 'completed'
            ]);

            return $examination->load(
                'appointment',
                'patient',
                'doctor.user',
                'doctor.specialty'
            );
        });
    }
    
    public function getDetail(User $user, Examination $examination){
        $this->checkDoctorCanAccessExamination($user, $examination);

        return $examination->load([
            'appointment',
            'patient',
            'doctor.user',
            'doctor.specialty'
        ]);
    }

    public function update(User $user, Examination $examination, array $data)
    {
        $this->checkDoctorCanAccessExamination($user, $examination);

        $examination->update($data);

        return $examination->refresh()->load([
            'appointment',
            'patient',
            'doctor.user',
            'doctor.specialty'
        ]);
    }

    private function checkDoctorCanAccessExamination(User $user, Examination $examination)
    {
        if ($user->doctor && $user->doctor->id !== $examination->doctor_id) {
            throw ValidationException::withMessages([
                'doctor_id' => 'You can only access your own examination'
            ]);
        }
    }
}