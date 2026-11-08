<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChargePayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'role_id', 'monthly_charge_id', 'amount', 'period_year', 'period_month',
    ];

    protected $casts = ['period_year' => 'integer', 'period_month' => 'integer'];

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
