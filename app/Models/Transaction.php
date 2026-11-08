<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'payable_type', 'payable_id', 'user_id', 'role_id', 'amount', 'gateway',
        'authority', 'reference_id', 'status', 'meta',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'meta' => 'array',
    ];
}
