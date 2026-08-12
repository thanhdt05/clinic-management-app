<?php

namespace App\Services;

use App\Constants\Messages\PrescriptionMessage;
use App\Models\Examination;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class PrescriptionService
{
    public function store(User $user, array $data): Prescription
    {
        $examination = Examination::query()
            ->findOrFail($data['examination_id']);

        $this->ensureDoctorOwnExamAndExamHasNoPrescription($user, $examination);

        $prescription = Prescription::create([
            'examination_id' => $examination->id,
            'doctor_id' => $examination->doctor_id,
            'notes' => $data['notes'] ?? null,
        ]);

        if (! empty($data['items'])) {
            $prescription->items()->createMany($data['items']);
        }

        return $prescription->load([
            'examination.patient',
            'doctor.user',
            'items.medicine',
        ]);
    }

    public function ensureDoctorOwnExamAndExamHasNoPrescription(User $user, Examination $examination): void
    {
        if ($examination->prescription()->exists()) {
            throw ValidationException::withMessages([
                'examination_id' => PrescriptionMessage::EXAMINATION_ALREADY_HAS_PRESCRIPTION,
            ]);
        }

        if ($user->doctor && $user->doctor->id !== $examination->doctor_id) {
            abort(
                Response::HTTP_FORBIDDEN,
                PrescriptionMessage::UNAUTHORIZED_EXAMINATION_PRESCRIPTION
            );
        }
    }
}