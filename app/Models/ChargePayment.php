<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Records a monthly charge against a unit role. The Jalali year and month let the same role pay the same charge again in a later period.
 */
class ChargePayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'role_id', 'monthly_charge_id', 'amount', 'period_year', 'period_month',
    ];

    protected $casts = ['period_year' => 'integer', 'period_month' => 'integer'];

    public function role(): BelongsTo
    {
        return $this->belongsTo(UserRole::class);
    }

    public function MonthlyCharge(): BelongsTo
    {
        return $this->belongsTo(MonthlyCharge::class);
    }

    public function transaction(): HasOne
    {
        return $this->hasOne(Transaction::class, 'payment_id')->where('payable_type', MonthlyCharge::class)
            ->where('status', Transaction::PAID);
    }
}
