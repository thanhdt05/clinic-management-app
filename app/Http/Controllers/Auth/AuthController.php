<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\User\UserResource;
use App\Models\User;
use App\Traits\HttpResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    use HttpResponse;

    public function login(LoginRequest $request)
    {
        $validate = $request->validated();

        $user = User::where('email', $validate['email'])->first();

        if (!$user || !Hash::check($validate['password'], $user->password)) {
            return $this->error([], 'Invalid credentials', 401);
        }

        if (!$user->is_active) {
            return $this->error([], 'Account is inactive', 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->success([
            'user' => UserResource::make($user),
            'token' => $token,
        ], 'Login successful.');
    }

    public function logout(Request $request)
    {
        $token = $request->user()?->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        return $this->success([], 'Logout successful.');
    }

    public function me(Request $request)
    {
        $user = $request->user()->load('role.permissions');

        return $this->success(
            ['user' => UserResource::make($user)],
            'User profile retrieved successfully.'
        );
    }
}
