<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PrescriptionResource extends JsonResource
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
            'examinations' => [
                'id' => $this->examination->id,

                'patient' => [
                    'id' => $this->examination->patient->id,
                    'code' => $this->examination->patient->code,
                    'full_name' => $this->examination->patient->full_name,
                ],
            ],

            'doctor' => [
                'id' => $this->doctor->id,
                'name' => $this->doctor->user->name,
                'license_number' => $this->doctor->license_number,
            ],

            'notes' => $this->notes,

            'items' => $this->items->map(
                fn ($item) => [
                    'id' => $item->id,

                    'medicine' => [
                        'id' => $item->medicine_id,
                        'code' => $item->medicine->code,
                        'name' => $item->medicine->name,
                        'unit' => $item->medicine->unit,
                    ],

                    'quantity' => $item->quantity,
                    'dosage' => $item->dosage,
                    'usage_instruction' => $item->usage_instruction,
                ]
            ),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
