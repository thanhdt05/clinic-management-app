<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrescriptionItem extends Model
{
    protected $fillable = [
        'prescription_id',
        'medicine_id',
        'quantity',
        'dosage',
        'usage_instruction',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    public function prescription()
    {
        return $this->belongsTo(Prescription::class);
    }

    public function medicine()
    {
        return $this->belongsTo(Medicine::class)->withTrashed();
    }
}
