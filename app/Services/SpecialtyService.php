<?php

namespace App\Services;

use App\Constants\ActivityAction;
use App\Events\ActivityLogged;
use App\Models\Specialty;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

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
        $specialty = Specialty::create($data);

        ActivityLogged::dispatch(
            ActivityAction::SPECIALTY_CREATED,
            $specialty,
            Auth::user(),
            ['name' => $specialty->name, 'description' => $specialty->description]
        );

        return $specialty;
    }

    public function update(Specialty $specialty, array $data): Specialty
    {
        $specialty->update($data);

        $loadedSpecialty = $specialty->refresh();

        ActivityLogged::dispatch(
            ActivityAction::SPECIALTY_UPDATED,
            $loadedSpecialty,
            Auth::user(),
            ['updated_fields' => array_keys($data)]
        );

        return $loadedSpecialty;
    }

    public function delete(Specialty $specialty): void
    {
        ActivityLogged::dispatch(
            ActivityAction::SPECIALTY_DELETED,
            $specialty,
            Auth::user(),
            ['name' => $specialty->name]
        );

        $specialty->delete();
    }
}
