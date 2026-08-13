<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
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
            'invoice_id' => $this->invoice_id,

            'amount' => $this->amount,
            'method' => $this->method,
            'status' => $this->status,

            'provider' => $this->provider,
            'provider_order_id' => $this->provider_order_id,
            'provider_capture_id' => $this->provider_capture_id,

            'paid_at' => $this->paid_at,

            'note' => $this->note,

            'invoice' => $this->whenLoaded(
                'invoice',
                fn () => [
                    'id' => $this->invoice->id,
                    'invoice_code' => $this->invoice->invoice_code,
                    'total' => $this->invoice->total,
                    'status' => $this->invoice->status,
                ]
            ),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
