<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageReaction extends Model
{
    public const PALETTE = ['👍', '❤️', '😂', '😮', '😢', '🙏', '🎉'];

    public $timestamps = false;

    protected $fillable = ['user_id', 'emoji'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
