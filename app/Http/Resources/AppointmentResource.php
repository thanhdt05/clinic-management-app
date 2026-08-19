<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
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
            'scheduled_at' => $this->scheduled_at?->format('Y-m-d H:i:s'),
            'status' => $this->status,
            'reason' => $this->reason,

            'patient' => $this->patient ? [
                'id' => $this->patient->id,
                'code' => $this->patient->code,
                'full_name' => $this->patient->full_name,
                'phone' => $this->patient->phone,
            ] : null,

            'doctor' => $this->doctor ? [
                'id' => $this->doctor->id,
                'name' => $this->doctor->user?->name,
                'license_number' => $this->doctor->license_number,
                'specialty' => $this->doctor->specialty ? [
                    'id' => $this->doctor->specialty->id,
                    'name' => $this->doctor->specialty->name,
                ] : null,
            ] : null,

            'created_at' => $this->created_at?->format('H:i d-m-Y'),
            'updated_at' => $this->updated_at?->format('H:i d-m-Y'),
        ];
    }
}
