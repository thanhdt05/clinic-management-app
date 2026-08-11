<?php

namespace App\Http\Controllers\Auth;

use App\Constants\Messages\AuthMessage;
use App\Http\Controllers\Controller;
use App\Http\Resources\User\UserResource;
use App\Models\User;
use App\Traits\HttpResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    use HttpResponse;

    public function login(Request $request): JsonResponse
    {
        $validate = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $validate['email'])->first();

        if (! $user || ! Hash::check($validate['password'], $user->password)) {
            return $this->error(
                [],
                AuthMessage::INVALID_CREDENTIALS,
                Response::HTTP_UNAUTHORIZED
            );
        }

        if (! $user->is_active) {
            return $this->error(
                [],
                AuthMessage::ACCOUNT_INACTIVE,
                Response::HTTP_UNAUTHORIZED
            );
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->success([
            'user' => UserResource::make($user),
            'token' => $token,
        ], AuthMessage::LOGIN_SUCCESS);
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()?->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        return $this->success(
            [],
            AuthMessage::LOGOUT_SUCCESS
        );
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('role.permissions');

        return $this->success([
            'user' => UserResource::make($user),
        ], AuthMessage::PROFILE_RETRIEVED);
    }
}
