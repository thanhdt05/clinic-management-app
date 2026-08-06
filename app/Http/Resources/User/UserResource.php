<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'is_active' => $this->is_active,
            'role' => [
                'id' => $this->role->id,
                'name' => $this->role->name,
                'display_name' => $this->role->display_name
            ],
            'permissions' => $this->role->permissions->pluck('name')->values(),
        ];
    }
}
