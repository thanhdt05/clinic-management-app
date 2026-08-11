<?php

namespace App\Services;

use App\Models\Specialty;
use Illuminate\Pagination\LengthAwarePaginator;

class SpecialtyService
{
    private const int PER_PAGE = 10;

    public function getAll(): LengthAwarePaginator
    {
        return Specialty::query()
            ->orderBy('name')
            ->paginate(self::PER_PAGE);
    }

    public function create(array $data): Specialty
    {
        return Specialty::create($data);
    }

    public function update(Specialty $specialty, array $data): Specialty
    {
        $specialty->update($data);
        return $specialty->refresh();
    }

    public function delete(Specialty $specialty): void
    {
        $specialty->delete();
    }
}