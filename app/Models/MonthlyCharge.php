<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MonthlyCharge extends Model
{
    use HasFactory;

    protected $fillable = [
        'complex_id', 'user_id', 'amount', 'start_date', 'end_date'
    ];

    // Relationships
    public function complex(): BelongsTo
    {
        return $this->belongsTo(Complex::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function chargePayments(): HasMany
    {
        return $this->hasMany(ChargePayment::class);
    }
}
