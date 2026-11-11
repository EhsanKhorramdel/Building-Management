<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A building with units, scoped roles, announcements, charges, incidental costs, polls and chat groups.
 */
class Complex extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'address', 'location', 'number_of_floors', 'number_of_units', 'join_link', 'latitude', 'longitude',
    ];

    protected $casts = [
        'latitude' => 'float', 'longitude' => 'float',
        'join_link_expires_at' => 'datetime', 'join_link_uses_left' => 'integer',
    ];

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    public function userRoles(): HasMany
    {
        return $this->hasMany(UserRole::class);
    }

    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class);
    }

    public function monthlyCharges(): HasMany
    {
        return $this->hasMany(MonthlyCharge::class);
    }

    public function incidentalCosts(): HasMany
    {
        return $this->hasMany(IncidentalCost::class);
    }

    public function groups(): HasMany
    {
        return $this->hasMany(Group::class);
    }

    public function polls(): HasMany
    {
        return $this->hasMany(Poll::class);
    }
}
