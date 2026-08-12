<?php

namespace App\Services;

use App\Constants\Messages\PrescriptionMessage;
use App\Models\Examination;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
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

    public function addItem(User $user, Prescription $prescription, array $data): Prescription
    {
        $this->enforceDoctorOwnership($user, $prescription);

        $prescription->items()->create($data);

        return $prescription->refresh()->load([
            'examination.patient',
            'doctor.user',
            'items.medicine',
        ]);
    }

    public function updateItem(User $user, Prescription $prescription, PrescriptionItem $prescriptionItem, array $data): Prescription
    {
        $this->enforceDoctorOwnership($user, $prescription);
        $this->ensurePrescriptionHasItem($prescription, $prescriptionItem);

        $prescriptionItem->update($data);

        return $prescription->refresh()->load([
            'examination.patient',
            'doctor.user',
            'items.medicine',
        ]);
    }

    public function removeItem(User $user, Prescription $prescription, PrescriptionItem $prescriptionItem): Prescription
    {
        $this->enforceDoctorOwnership($user, $prescription);
        $this->ensurePrescriptionHasItem($prescription, $prescriptionItem);

        $prescriptionItem->delete();

        return $prescription->refresh()->load([
            'examination.patient',
            'doctor.user',
            'items.medicine',
        ]);
    }


    public function enforceDoctorOwnership(User $user, Prescription $prescription): void
    {
        if ($user->doctor && $user->doctor->id !== $prescription->doctor_id) {
            abort(
                Response::HTTP_FORBIDDEN,
                PrescriptionMessage::UNAUTHORIZED_PRESCRIPTION
            );
        }
    }

    public function ensurePrescriptionHasItem(Prescription $prescription, PrescriptionItem $prescriptionItem): void
    {
        if ($prescriptionItem->prescription_id !== $prescription->id) {
            abort(
                Response::HTTP_NOT_FOUND,
                PrescriptionMessage::PRESCRIPTION_ITEM_NOT_FOUND
            );
        }
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