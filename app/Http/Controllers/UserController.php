<?php

namespace App\Http\Controllers;

use App\Constants\Messages\UserMessage;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Requests\User\UpdateUserStatusRequest;
use App\Http\Resources\User\UserResource;
use App\Models\User;
use App\Services\UserService;
use App\Traits\HttpResponse;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

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
            UserMessage::USER_LIST_RETRIEVED
        );
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->userService->create(
            $request->validated()
        );

        return $this->success(
            UserResource::make($user),
            UserMessage::USER_CREATED,
            Response::HTTP_CREATED
        );
    }

    public function show(User $user): JsonResponse
    {
        return $this->success(
            UserResource::make($user->load(['role.permissions'])),
            UserMessage::USER_DETAILS_RETRIEVED
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
            UserMessage::USER_UPDATED
        );
    }

    public function destroy(User $user): JsonResponse
    {
        $this->userService->deactivate($user);

        return $this->success(
            [],
            UserMessage::USER_DELETED
        );
    }

    public function updateStatus(UpdateUserStatusRequest $request, User $user): JsonResponse
    {
        $user = $this->userService->updateStatus(
            $user,
            $request->validated()['is_active']
        );

        return $this->success(
            UserResource::make($user),
            UserMessage::USER_STATUS_UPDATED
        );
    }
}
