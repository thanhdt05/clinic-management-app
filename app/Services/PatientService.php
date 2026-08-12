<?php

namespace App\Services;

use App\Models\Patient;
use Illuminate\Pagination\LengthAwarePaginator;

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

        return Patient::create($data);
    }

    public function update(Patient $patient, array $data): Patient
    {
        $patient->update($data);

        return $patient->refresh();
    }

    public function delete(Patient $patient): void
    {
        $patient->delete();
    }

    public function generateCode(): string
    {
        $number = Patient::withTrashed()->max('id') + 1;
        $code = 'BN-'.str_pad($number, 6, '0', STR_PAD_LEFT);

        return $code;
    }
}
