<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['actor_id', 'complex_id', 'event', 'subject_type', 'subject_id', 'meta'];

    protected $casts = ['meta' => 'array'];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function complex(): BelongsTo
    {
        return $this->belongsTo(Complex::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
