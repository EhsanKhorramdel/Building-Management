<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    use HasFactory;
    protected $fillable = [
        'complex_id', 'zip_code', 'unit_number', 'floor_number'
    ];

    // Relationships
    public function complex(): BelongsTo
    {
        return $this->belongsTo(Complex::class);
    }

    public function userRoles(): HasMany
    {
        return $this->hasMany(UserRoles::class);
    }
}
