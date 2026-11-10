<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A timed ballot in a complex with numbered options and votes tied to unit roles.
 */
class Poll extends Model
{
    use HasFactory;

    protected $fillable = [
        'complex_id', 'title', 'question', 'start_date', 'end_date'
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    public function complex(): BelongsTo
    {
        return $this->belongsTo(Complex::class);
    }

    public function pollOptions(): HasMany
    {
        return $this->hasMany(PollOption::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }
    public function isExpired(): bool
    {
        return Carbon::now()->isAfter($this->end_date);
    }
}
