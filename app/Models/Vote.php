<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vote extends Model
{
    use HasFactory;

    protected $fillable = [
        'poll_id', 'role_id', 'poll_option_id'
    ];

    // Relationships
    public function poll(): BelongsTo
    {
        return $this->belongsTo(Poll::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(UserRole::class);
    }

    public function pollOption(): BelongsTo
    {
        return $this->belongsTo(PollOption::class);
    }
}
