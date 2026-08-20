<?php

namespace App\Services;

use App\Constants\ActivityAction;
use App\Events\ActivityLogged;
use App\Models\Patient;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class PatientService
{
    private const int PER_PAGE = 10;

    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $query = Patient::query()->latest();

        $keyword = $filters['q'] ?? $filters['keyword'] ?? $filters['search'] ?? null;
        if (isset($keyword)) {
            $keyword = trim($keyword);

            $query->where(function ($query) use ($keyword) {
                $query->where('full_name', 'ILIKE', "%$keyword%")
                    ->orWhere('phone', 'ILIKE', "%$keyword%")
                    ->orWhere('code', 'ILIKE', "%$keyword%");
            });
        }

        return $query->paginate($filters['per_page'] ?? self::PER_PAGE);
    }

    public function create(array $data): Patient
    {
        $data['code'] = $this->generateCode();

        $patient = Patient::create($data);

        ActivityLogged::dispatch(
            ActivityAction::PATIENT_CREATED,
            $patient,
            Auth::user(),
            [
                'code' => $patient->code,
                'full_name' => $patient->full_name,
                'phone' => $patient->phone,
            ]
        );

        return $patient;
    }

    public function update(Patient $patient, array $data): Patient
    {
        $patient->update($data);

        $loadedPatient = $patient->refresh();

        ActivityLogged::dispatch(
            ActivityAction::PATIENT_UPDATED,
            $loadedPatient,
            Auth::user(),
            [
                'code' => $loadedPatient->code,
                'updated_fields' => array_keys($data),
            ]
        );

        return $loadedPatient;
    }

    public function delete(Patient $patient): void
    {
        ActivityLogged::dispatch(
            ActivityAction::PATIENT_DELETED,
            $patient,
            Auth::user(),
            [
                'code' => $patient->code,
                'full_name' => $patient->full_name,
            ]
        );

        $patient->delete();
    }

    public function generateCode(): string
    {
        $number = Patient::withTrashed()->max('id') + 1;
        $code = 'BN-'.str_pad($number, 6, '0', STR_PAD_LEFT);

        return $code;
    }
}
