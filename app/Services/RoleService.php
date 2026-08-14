<?php

namespace App\Services;

use App\Models\Role;
use Illuminate\Pagination\LengthAwarePaginator;

class RoleService
{
    private const int PER_PAGE = 10;

    public function getAll(): LengthAwarePaginator
    {
        return Role::query()
            ->with(['permissions'])
            ->orderBy('id')
            ->paginate(self::PER_PAGE);
    }
}
