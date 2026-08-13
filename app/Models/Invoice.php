<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;
    protected $fillable = [
        'examination_id',
        'invoice_code',
        'subtotal',
        'discount',
        'total',
        'status',
        'issued_at',
    ];

    protected function cast(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',

            'issued_at' => 'datetime',
        ];
    }

    public function examination()
    {
        return $this->belongsTo(Examination::class);
    }
}
