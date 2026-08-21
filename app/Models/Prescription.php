<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prescription extends Model
{
    use HasFactory;

    protected $fillable = [
        'examination_id',
        'doctor_id',
        'notes',
    ];

    public function examination()
    {
        return $this->belongsTo(Examination::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function items()
    {
        return $this->hasMany(PrescriptionItem::class);
    }

    protected static function booted(): void
    {
        static::deleted(function (Prescription $prescription) {
            $prescription->items()->delete();
        });
    }
}
