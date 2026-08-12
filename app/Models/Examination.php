<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Examination extends Model
{
    use HasFactory;

    protected $fillable = [
        'doctor_id',
        'appointment_id',
        'patient_id',
        'diagnosis',
        'notes',
        'examined_at',
    ];

    protected $casts = [
        'examined_at' => 'datetime',
    ];

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function prescription(): HasOne
    {
        return $this->hasOne(Prescription::class);
    }
}
