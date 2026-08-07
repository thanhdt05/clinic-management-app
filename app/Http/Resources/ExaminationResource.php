<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExaminationResource extends JsonResource
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
            'appointment_id' => $this->appointment_id,
            'patient_id' => [
                'id' => $this->patient->id,
                'code' => $this->patient->code,
                'full_name' => $this->patient->full_name
            ],
            'doctor_id' => [
                'id' => $this->doctor->id,
                'name' => $this->doctor->user->name,
                'license_number' => $this->doctor->license_number
            ],
            'diagnosis' => $this->diagnosis,
            'notes' => $this->notes,
            
            'examined_at' => $this->examined_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
