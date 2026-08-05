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
            "id"=> $this->id,
            "user"=> [
                "id"=> $this->user->id,
                "name"=> $this->user->name,
                "email"=> $this->user->email,
                "is_active"=> $this->user->is_active,
            ],
            "specialty"=> [
                "id"=> $this->specialty_id,
                "name"=> $this->specialty->name,
            ],
            "license_number"=> $this->license_number,
            "bio"=> $this->bio,
            "created_at"=> $this->created_at?->format('H:i d-m-Y'),
            "updated_at"=> $this->updated_at?->format('H:i d-m-Y'),
        ];
    }
}
