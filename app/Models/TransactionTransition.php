<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

class TransactionTransition extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['from_state', 'to_state', 'actor_id'];

    protected static function booted(): void
    {
        $reject = fn () => throw new LogicException('Transaction transitions are immutable.');
        static::updating($reject);
        static::deleting($reject);
    }
}
