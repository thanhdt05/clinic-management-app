<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
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

        return $user->refresh()->load(['role.permissions']);
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

        return $user->refresh()->load(['role.permissions']);
    }

    public function deactivate(User $user): User
    {
        $this->isLastAdmin($user);
        $user->update([
            'is_active' => false,
        ]);

        return $user->refresh()->load(['role.permissions']);
    }

    public function updateStatus(User $user, bool $isActive): User
    {
        if (! $isActive) {
            $this->isLastAdmin($user);
        }

        $user->update([
            'is_active' => $isActive,
        ]);

        return $user->refresh()->load(['role.permissions']);
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
                'role_id' => 'Cannot change role or deactivate the last active Admin in the system!',
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
