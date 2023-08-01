<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChargePayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'role_id', 'monthly_charge_id', 'amount', 'date'
    ];

    // Relationships
    public function role(): BelongsTo
    {
        return $this->belongsTo(UserRole::class);
    }

    public function MonthlyCharge(): BelongsTo
    {
        return $this->belongsTo(MonthlyCharge::class);
    }
}
