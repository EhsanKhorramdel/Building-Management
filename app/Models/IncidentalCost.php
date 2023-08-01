<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IncidentalCost extends Model
{
    use HasFactory;

    protected $fillable = [
        'complex_id', 'cost_invoice', 'cost_explanation', 'title', 'total_amount', 'share_amount'
    ];

    // Relationships
    public function complex(): BelongsTo
    {
        return $this->belongsTo(Complex::class);
    }

    public function PaymentAdditionalCosts(): HasMany
    {
        return $this->hasMany(PaymentAdditionalCost::class, 'incidental_costs_id');
    }
}
