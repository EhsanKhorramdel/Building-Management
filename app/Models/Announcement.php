<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Announcement extends Model
{
    use HasFactory;

    public const ATTACHMENT_DIRECTORY = 'private/notificationFiles';

    protected $fillable = [
        'complex_id', 'manager_id', 'text', 'image_url', 'archive',
    ];

    protected $casts = ['pinned_at' => 'datetime', 'archive' => 'boolean'];

    public function reads(): HasMany
    {
        return $this->hasMany(AnnouncementRead::class);
    }

    public function complex(): BelongsTo
    {
        return $this->belongsTo(Complex::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }
}
