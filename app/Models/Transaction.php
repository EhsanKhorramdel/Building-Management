<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Transaction extends Model
{
    public const PENDING = 'pending';

    public const PAID = 'paid';

    public const FAILED = 'failed';

    public const CANCELLED = 'cancelled';

    public const EXPIRED = 'expired';

    protected $fillable = [
        'payable_type', 'payable_id', 'user_id', 'role_id', 'amount', 'gateway',
        'authority', 'reference_id', 'status', 'meta', 'payment_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'meta' => 'array',
    ];

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(UserRole::class);
    }

    public function transitions(): HasMany
    {
        return $this->hasMany(TransactionTransition::class)->orderBy('id');
    }

    public function callbackIssues(): HasMany
    {
        return $this->hasMany(TransactionCallbackIssue::class)->orderBy('id');
    }

    public function receiptUrl(): ?string
    {
        if ($this->status !== self::PAID || $this->payment_id === null) {
            return null;
        }

        return match ($this->payable_type) {
            MonthlyCharge::class => route('payments.receipt', ['charge', $this->payment_id]),
            IncidentalCost::class => route('payments.receipt', ['cost', $this->payment_id]),
            default => null,
        };
    }

    public function matchesAmount(mixed $amount): bool
    {
        if ((! is_string($amount) && ! is_int($amount) && ! is_float($amount))
            || ! preg_match('/^\d{1,10}(?:\.\d{1,2})?$/D', (string) $amount)) {
            return false;
        }

        return number_format((float) $amount, 2, '.', '') === $this->amount;
    }

    public function itemLabel(): string
    {
        return match (true) {
            $this->payable instanceof MonthlyCharge => __('integrations::payments.charge'),
            $this->payable instanceof IncidentalCost => $this->payable->title,
            default => __('integrations::payments.unavailable_item'),
        };
    }
}
