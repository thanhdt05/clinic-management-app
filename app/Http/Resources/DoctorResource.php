<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoctorResource extends JsonResource
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
            'user' => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'is_active' => $this->user->is_active,
            ] : null,
            'specialty' => $this->specialty ? [
                'id' => $this->specialty->id,
                'name' => $this->specialty->name,
            ] : null,
            'license_number' => $this->license_number,
            'bio' => $this->bio,
            'created_at' => $this->created_at?->format('H:i d-m-Y'),
            'updated_at' => $this->updated_at?->format('H:i d-m-Y'),
        ];
    }
}
