<?php

namespace App\Http\Controllers;

use App\Constants\Messages\RoleMessage;
use App\Http\Resources\RoleResource;
use App\Services\RoleService;
use App\Traits\HttpResponse;
use Illuminate\Http\JsonResponse;

class RoleController extends Controller
{
    use HttpResponse;

    public function __construct(
        private readonly RoleService $roleService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $roles = $this->roleService->getAll();

        return $this->paginated(
            RoleResource::collection($roles),
            $roles,
            RoleMessage::ROLE_LIST_RETRIEVED
        );
    }
}
