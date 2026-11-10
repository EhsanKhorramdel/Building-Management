<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsLog extends Model
{
    public const PENDING = 'pending';

    public const LOGGED = 'logged';

    public const SENT = 'sent';

    public const FAILED = 'failed';

    protected $fillable = ['recipient', 'template', 'body', 'status', 'driver', 'meta'];

    protected $casts = ['meta' => 'array'];
}
