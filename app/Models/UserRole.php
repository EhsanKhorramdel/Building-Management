<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserRole extends Model
{
    use HasFactory;

    protected $fillable = [
        'complex_id', 'unit_id', 'user_id', 'role', 'start_date', 'end_date', 'active'
    ];

    protected $casts = [
        'active' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    // Relationships
    public function complex(): BelongsTo
    {
        return $this->belongsTo(Complex::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function chargePayments(): HasMany
    {
        return $this->hasMany(ChargePayment::class, 'role_id');
    }

    public function paymentAdditionalCosts(): HasMany
    {
        return $this->hasMany(PaymentAdditionalCost::class, 'role_id');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class, 'role_id');
    }
}
