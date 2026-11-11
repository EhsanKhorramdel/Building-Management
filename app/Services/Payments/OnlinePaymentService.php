<?php

namespace App\Services\Payments;

use App\Models\IncidentalCost;
use App\Models\MonthlyCharge;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserRole;
use App\Services\PaymentService;
use App\Services\SmsService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;
use Morilog\Jalali\Jalalian;

class OnlinePaymentService
{
    public function __construct(private PaymentService $payments, private GatewayManager $gateways) {}

    public function initiate(array $data, User $user): PaymentRedirect
    {
        $payable = $data['type'] === 'charge'
            ? MonthlyCharge::findOrFail($data['payable_id'])
            : IncidentalCost::findOrFail($data['payable_id']);
        Gate::forUser($user)->authorize('view', $payable->complex);

        $driver = $this->gateways->getDefaultDriver();
        try {
            $gateway = $this->gateways->driver($driver);
        } catch (InvalidArgumentException) {
            throw new PaymentException('unconfigured');
        }

        $transaction = DB::transaction(function () use ($data, $user, $payable, $driver) {
            $role = $user->userRoles()->where('complex_id', $payable->complex_id)
                ->whereIn('role', ['manager', 'resident'])->where('active', true)
                ->whereHas('unit', fn ($query) => $query->where('unit_number', $data['unitNumber'])
                    ->where('complex_id', $payable->complex_id))
                ->lockForUpdate()->first();

            if (! $role) {
                throw new PaymentException('unit');
            }

            $period = Jalalian::fromCarbon(now());
            if ($payable instanceof MonthlyCharge && ! $this->payments->chargeHasStarted($payable, $period)) {
                throw new PaymentException('not_started');
            }
            if ($this->payments->alreadyPaid($payable, $role, $period)) {
                throw new PaymentException('already_paid');
            }
            if (Transaction::query()->whereMorphedTo('payable', $payable)->where('role_id', $role->id)
                ->where('status', Transaction::PENDING)->where('created_at', '>', $this->expiryCutoff())->exists()) {
                throw new PaymentException('pending_attempt');
            }

            $amount = $this->payments->payableAmount($payable);
            if ($amount <= 0) {
                throw new PaymentException('invalid_amount');
            }

            $transaction = Transaction::create([
                'payable_type' => $payable->getMorphClass(),
                'payable_id' => $payable->id,
                'user_id' => $user->id,
                'role_id' => $role->id,
                'amount' => $amount,
                'gateway' => $driver,
                'status' => Transaction::PENDING,
            ]);
            $transaction->transitions()->create(['from_state' => null, 'to_state' => Transaction::PENDING, 'actor_id' => $user->id]);

            return $transaction;
        });

        try {
            $checkout = $gateway->initiate($transaction);
            $transaction->update(['authority' => $checkout->authority]);
        } catch (PaymentException|UniqueConstraintViolationException $exception) {
            $this->finish($transaction, Transaction::FAILED, 'initiation_failed');

            throw $exception instanceof PaymentException ? $exception : new PaymentException('unavailable');
        }

        return $checkout;
    }

    public function callback(string $driver, array $payload, ?int $viewerId): Transaction
    {
        $authority = data_get($payload, $this->gateways->callbackAuthority($driver));
        abort_unless(is_string($authority) && $authority !== '' && strlen($authority) <= 255, 404);
        $completed = false;

        $transaction = DB::transaction(function () use ($driver, $authority, $payload, $viewerId, &$completed) {
            $attempt = Transaction::query()->where('gateway', $driver)->where('authority', $authority);
            // A no-op write serializes verification on SQLite, which ignores SELECT FOR UPDATE.
            (clone $attempt)->toBase()->update(['status' => DB::raw('status')]);
            $transaction = $attempt->firstOrFail();
            abort_if($driver === 'mock' && $transaction->user_id !== $viewerId, 403);
            $actorId = $driver === 'mock' ? $viewerId : null;

            if ($transaction->status !== Transaction::PENDING) {
                $transaction->callbackIssues()->firstOrCreate(['kind' => 'duplicate_callback']);

                return $transaction;
            }
            if ($transaction->created_at->lte($this->expiryCutoff())) {
                return $this->finish($transaction, Transaction::EXPIRED, 'expired', actorId: $actorId);
            }

            $verification = $this->gateways->driver($driver)->verify(['transaction' => $transaction, 'payload' => $payload]);
            if (! $verification->success) {
                $status = $verification->failureReason === 'cancelled' ? Transaction::CANCELLED : Transaction::FAILED;
                if (in_array($verification->failureReason, ['amount_mismatch', 'invalid_status'], true)) {
                    $transaction->callbackIssues()->firstOrCreate(['kind' => 'mismatch']);
                }

                return $this->finish($transaction, $status, $verification->failureReason, actorId: $actorId);
            }

            $payable = $transaction->payable;
            $role = UserRole::query()->lockForUpdate()->find($transaction->role_id);
            $period = Jalalian::fromCarbon(now());
            if (! ($payable instanceof MonthlyCharge || $payable instanceof IncidentalCost) || ! $role
                || $role->complex_id !== $payable->complex_id
                || ! $transaction->matchesAmount($this->payments->payableAmount($payable))
                || $this->payments->alreadyPaid($payable, $role, $period)) {
                $transaction->callbackIssues()->firstOrCreate(['kind' => 'mismatch']);

                return $this->finish($transaction, Transaction::FAILED, 'payment_conflict', $verification->referenceId, $actorId);
            }

            try {
                $payment = $this->payments->recordPayment($payable, $role, $period);
            } catch (UniqueConstraintViolationException) {
                $transaction->callbackIssues()->firstOrCreate(['kind' => 'mismatch']);

                return $this->finish($transaction, Transaction::FAILED, 'payment_conflict', $verification->referenceId, $actorId);
            }
            $completed = true;

            return $this->finish($transaction, Transaction::PAID, reference: $verification->referenceId, actorId: $actorId, paymentId: $payment->id);
        });

        if ($completed) {
            app(SmsService::class)->queue('payment_receipt', $transaction->id);
        }

        return $transaction;
    }

    private function expiryCutoff(): Carbon
    {
        return now()->subMinutes(max(1, config('payments.expiry_minutes')));
    }

    private function finish(Transaction $transaction, string $status, ?string $reason = null, ?string $reference = null, ?int $actorId = null, ?int $paymentId = null): Transaction
    {
        return DB::transaction(function () use ($transaction, $status, $reason, $reference, $actorId, $paymentId) {
            $transaction = Transaction::lockForUpdate()->findOrFail($transaction->id);
            if ($transaction->status !== Transaction::PENDING) {
                return $transaction;
            }

            $transaction->update([
                'status' => $status,
                'reference_id' => $reference,
                'payment_id' => $paymentId,
                'meta' => $reason === null ? $transaction->meta : ['failure_reason' => $reason],
            ]);
            $transaction->transitions()->create([
                'from_state' => Transaction::PENDING,
                'to_state' => $status,
                'actor_id' => $actorId,
            ]);

            return $transaction;
        });
    }
}
