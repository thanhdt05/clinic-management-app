<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Requests\User\UpdateUserStatusRequest;
use App\Http\Resources\User\UserResource;
use App\Models\User;
use App\Services\UserService;
use App\Traits\HttpResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    use HttpResponse;

    public function __construct(
        private readonly UserService $userService
    ) {}

    public function index(): JsonResponse
    {
        $users = $this->userService->getAll();

        return $this->paginated(
            UserResource::collection($users),
            $users,
            'User list retrieved successfully.'
        );
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->userService->create(
            $request->validated()
        );

        return $this->success(
            UserResource::make($user),
            'User created successfully.',
            201
        );
    }

    public function show(User $user): JsonResponse
    {
        return $this->success(
            UserResource::make($user->load(['role.permissions'])),
            'User details retrieved successfully.'
        );
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $user = $this->userService->update(
            $user,
            $request->validated()
        );

        return $this->success(
            UserResource::make($user),
            'User updated successfully.'
        );
    }

    public function destroy(User $user)
    {
        $this->userService->deactivate($user);

        return $this->success(
            [],
            'User deleted successfully.',
        );
    }

    public function updateStatus(UpdateUserStatusRequest $request, User $user)
    {
        $user = $this->userService->updateStatus(
            $user,
            $request->validated()['is_active']
        );

        return $this->success(
            UserResource::make($user),
            'User status updated successfully.',
        );
    }
}
