<?php

namespace App\Services;

use App\Constants\Messages\PrescriptionMessage;
use App\Models\Examination;
use App\Models\Medicine;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class PrescriptionService
{
    private const int PER_PAGE = 10;

    public function getAll(User $user, array $filters): LengthAwarePaginator
    {
        $query = Prescription::query()
            ->with([
                'examination.patient',
                'doctor.user',
                'items.medicine',
            ])
            ->latest();

        if ($user->doctor) {
            $query->where('doctor_id', $user->doctor->id);
        }

        if (isset($filters['doctor_id'])) {
            $query->where('doctor_id', $filters['doctor_id']);
        }

        return $query->paginate($filters['per_page'] ?? self::PER_PAGE);
    }

    public function getDetail(User $user, Prescription $prescription): Prescription
    {
        if ($user->doctor && $user->doctor->id !== $prescription->doctor_id) {
            abort(
                Response::HTTP_FORBIDDEN,
                PrescriptionMessage::UNAUTHORIZED_PRESCRIPTION
            );
        }

        return $prescription->load([
            'examination.patient',
            'doctor.user',
            'items.medicine',
        ]);
    }

    public function update(User $user, Prescription $prescription, array $data): Prescription
    {
        $this->enforceDoctorOwnership($user, $prescription);

        $prescription->update([
            'notes' => $data['notes'] ?? null,
        ]);

        return $prescription->load([
            'examination.patient',
            'doctor.user',
            'items.medicine',
        ]);
    }

    public function store(User $user, array $data): Prescription
    {
        return DB::transaction(function () use ($user, $data) {
            $examination = Examination::query()
                ->findOrFail($data['examination_id']);

            $this->ensureDoctorOwnExamAndExamHasNoPrescription($user, $examination);

            $prescription = Prescription::create([
                'examination_id' => $examination->id,
                'doctor_id' => $examination->doctor_id,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] ?? [] as $item) {
                $this->createItemAndUpdateStock($prescription, $item);
            }

            return $prescription->load([
                'examination.patient',
                'doctor.user',
                'items.medicine',
            ]);
        });
    }

    public function addItem(User $user, Prescription $prescription, array $data): Prescription
    {
        return DB::transaction(function () use ($user, $prescription, $data) {
            $this->enforceDoctorOwnership($user, $prescription);

            $this->createItemAndUpdateStock($prescription, $data);

            return $prescription->refresh()->load([
                'examination.patient',
                'doctor.user',
                'items.medicine',
            ]);
        });
    }

    public function updateItem(User $user, Prescription $prescription, PrescriptionItem $prescriptionItem, array $data): Prescription
    {
        return DB::transaction(function () use ($user, $prescription, $prescriptionItem, $data) {
            $this->enforceDoctorOwnership($user, $prescription);

            $lockedItem = PrescriptionItem::query()
                ->where('prescription_id', $prescription->id)
                ->lockForUpdate()
                ->findOrFail($prescriptionItem->id);

            $oldQuantity = $lockedItem->quantity;
            $newQuantity = $data['quantity'] ?? $oldQuantity;

            $delta = $newQuantity - $oldQuantity;

            if ($delta !== 0) {
                $medicine = Medicine::query()
                    ->lockForUpdate()
                    ->findOrFail($lockedItem->medicine_id);

                if ($delta > 0) {
                    if ($medicine->stock < $delta) {
                        throw ValidationException::withMessages(
                            ['quantity' => 'Insufficient stock for medicine.']
                        );
                    }

                    $medicine->decrement('stock', $delta);
                }

                if ($delta < 0) {
                    $medicine->increment('stock', abs($delta));
                }
            }

            $lockedItem->update($data);

            return $prescription->refresh()->load([
                'examination.patient',
                'doctor.user',
                'items.medicine',
            ]);
        });
    }

    public function removeItem(User $user, Prescription $prescription, PrescriptionItem $prescriptionItem): Prescription
    {
        return DB::transaction(function () use ($user, $prescription, $prescriptionItem) {
            $this->enforceDoctorOwnership($user, $prescription);

            $lockedItem = PrescriptionItem::query()
                ->where('prescription_id', $prescription->id)
                ->lockForUpdate()
                ->findOrFail($prescriptionItem->id);

            $medicine = Medicine::query()
                ->lockForUpdate()
                ->findOrFail($lockedItem->medicine_id);

            $medicine->increment('stock', $lockedItem->quantity);
            $lockedItem->delete();

            return $prescription->refresh()->load([
                'examination.patient',
                'doctor.user',
                'items.medicine',
            ]);
        });
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

    public function createItemAndUpdateStock(Prescription $prescription, array $data): void
    {
        $medicine = Medicine::query()
            ->lockForUpdate()
            ->findOrFail($data['medicine_id']);

        if (! $medicine->is_active) {
            throw ValidationException::withMessages(
                ['medicine_id' => 'Inactive medicine cannot be prescribed.']
            );
        }

        if ($medicine->stock < $data['quantity']) {
            throw ValidationException::withMessages([
                'quantity' => "Insufficient stock for {$medicine->name}. Please update the stock first.",
            ]);
        }

        $prescription->items()->create([
            'medicine_id' => $medicine->id,
            'quantity' => $data['quantity'],
            'dosage' => $data['dosage'] ?? null,
            'usage_instruction' => $data['usage_instruction'] ?? null,
        ]);

        $medicine->decrement('stock', $data['quantity']);
    }
}
