<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PaymentAdditionalCost extends Model
{
    use HasFactory;

    protected $fillable = [
        'role_id', 'incidental_costs_id', 'amount',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(UserRole::class);
    }

    public function incidentalCost(): BelongsTo
    {
        return $this->belongsTo(IncidentalCost::class, 'incidental_costs_id');
    }

    public function transaction(): HasOne
    {
        return $this->hasOne(Transaction::class, 'payment_id')->where('payable_type', IncidentalCost::class)
            ->where('status', Transaction::PAID);
    }
}
