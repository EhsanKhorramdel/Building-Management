<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsLog extends Model
{
    protected $fillable = ['recipient', 'template', 'body', 'status', 'driver', 'meta'];

    protected $casts = ['meta' => 'array'];
}
