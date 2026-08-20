<?php

namespace App\Services;

use App\Constants\ActivityAction;
use App\Constants\Messages\UserMessage;
use App\Events\ActivityLogged;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserService
{
    private const int PER_PAGE = 10;

    public function getAll(): LengthAwarePaginator
    {
        return User::query()
            ->with(['role.permissions'])
            ->latest()
            ->paginate(self::PER_PAGE);
    }

    public function create(array $data): User
    {
        $data['password'] = Hash::make($data['password']);
        $user = User::create($data);

        $loadedUser = $user->refresh()->load(['role.permissions']);

        ActivityLogged::dispatch(
            ActivityAction::USER_CREATED,
            $loadedUser,
            Auth::user(),
            [
                'email' => $loadedUser->email,
                'name' => $loadedUser->name,
                'role' => $loadedUser->role?->name,
            ],
        );

        return $loadedUser;
    }

    public function update(User $user, array $data): User
    {
        if (isset($data['role_id']) && (int) $data['role_id'] !== $user->role_id) {
            $this->isLastAdmin($user);
        }

        if (array_key_exists('password', $data)) {
            if (! empty($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            } else {
                unset($data['password']);
            }
        }

        $user->update($data);

        $loadedUser = $user->refresh()->load(['role.permissions']);

        ActivityLogged::dispatch(
            ActivityAction::USER_UPDATED,
            $loadedUser,
            Auth::user(),
            [
                'updated_fields' => array_keys($data),
            ],
        );

        return $loadedUser;
    }

    public function deactivate(User $user): User
    {
        $this->isLastAdmin($user);
        $user->update([
            'is_active' => false,
        ]);

        $loadedUser = $user->refresh()->load(['role.permissions']);

        ActivityLogged::dispatch(
            ActivityAction::USER_DEACTIVATED,
            $loadedUser,
            Auth::user(),
            ['is_active' => false]
        );

        return $loadedUser;
    }

    public function updateStatus(User $user, bool $isActive): User
    {
        if (! $isActive) {
            $this->isLastAdmin($user);
        }

        $user->update([
            'is_active' => $isActive,
        ]);

        $loadedUser = $user->refresh()->load(['role.permissions']);

        ActivityLogged::dispatch(
            ActivityAction::USER_STATUS_CHANGED,
            $loadedUser,
            Auth::user(),
            ['is_active' => $isActive],
        );

        return $loadedUser;
    }

    public function isLastAdmin(User $user): void
    {
        if ($user->role?->name !== 'ADMIN') {
            return;
        }

        if (! $user->is_active) {
            return;
        }

        if ($this->countActiveAdmin() <= 1) {
            throw ValidationException::withMessages([
                'role_id' => UserMessage::CANNOT_MODIFY_LAST_ADMIN,
            ]);
        }
    }

    public function countActiveAdmin(): int
    {
        return User::query()
            ->where('is_active', true)
            ->whereHas('role', function ($query) {
                $query->where('name', 'ADMIN');
            })
            ->count();
    }
}
