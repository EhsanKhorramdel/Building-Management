<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentAdditionalCost extends Model
{
    use HasFactory;

    protected $fillable = [
        'role_id', 'incidental_costs_id', 'amount'
    ];

    // Relationships
    public function role(): BelongsTo
    {
        return $this->belongsTo(UserRole::class);
    }

    public function incidentalCost(): BelongsTo
    {
        return $this->belongsTo(IncidentalCost::class);
    }
}
