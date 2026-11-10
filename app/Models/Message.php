<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A chat entry in a complex group, with an author and per-user seen receipts.
 */
class Message extends Model
{
    use HasFactory;

    public const ATTACHMENT_DIRECTORY = 'private/chats';

    protected $fillable = [
        'group_id', 'user_id', 'text', 'image_url', 'deleted', 'edited', 'parent_id',
    ];

    protected $casts = [
        'deleted' => 'boolean',
        'edited' => 'boolean',
        'pinned_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function seenMessages(): HasMany
    {
        return $this->hasMany(SeenMessage::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(MessageReaction::class);
    }
}
