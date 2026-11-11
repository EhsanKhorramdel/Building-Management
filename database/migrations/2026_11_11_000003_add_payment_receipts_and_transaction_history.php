<?php

use App\Models\IncidentalCost;
use App\Models\MonthlyCharge;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_transitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained();
            $table->string('from_state')->nullable();
            $table->string('to_state');
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('transaction_callback_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained();
            $table->string('kind');
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['transaction_id', 'kind'], 'transaction_callback_issue_kind_unique');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->unique(['payable_type', 'payment_id'], 'transactions_payment_record_unique');
            $table->index(['user_id', 'id'], 'transactions_user_history_index');
        });
        if (DB::getDriverName() === 'mysql' && Schema::hasIndex('transactions', 'transactions_user_id_foreign')) {
            Schema::table('transactions', fn (Blueprint $table) => $table->dropIndex('transactions_user_id_foreign'));
        }

        $this->protectTransitions();
        $this->linkExistingPayments();
    }

    private function protectTransitions(): void
    {
        foreach (['UPDATE', 'DELETE'] as $operation) {
            $name = 'transaction_transitions_no_'.strtolower($operation);
            if (DB::getDriverName() === 'sqlite') {
                DB::unprepared("CREATE TRIGGER $name BEFORE $operation ON transaction_transitions BEGIN SELECT RAISE(ABORT, 'Transaction transitions are immutable'); END");
            } elseif (DB::getDriverName() === 'mysql') {
                DB::unprepared("CREATE TRIGGER $name BEFORE $operation ON transaction_transitions FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Transaction transitions are immutable'");
            }
        }
    }

    private function linkExistingPayments(): void
    {
        $types = [
            MonthlyCharge::class => ['charge_payments', 'monthly_charge_id'],
            IncidentalCost::class => ['payment_additional_costs', 'incidental_costs_id'],
        ];
        DB::table('transactions')->where('status', 'paid')->whereIn('payable_type', array_keys($types))
            ->whereNotNull('role_id')->orderBy('id')->chunkById(100, function ($attempts) use ($types) {
                foreach ($attempts as $attempt) {
                    [$table, $column] = $types[$attempt->payable_type];
                    $payments = DB::table($table)->where('role_id', $attempt->role_id)
                        ->where($column, $attempt->payable_id)->where('amount', $attempt->amount)
                        ->whereBetween('created_at', [$attempt->created_at, $attempt->updated_at])->limit(2)->get();
                    if ($payments->count() !== 1) {
                        continue;
                    }
                    $payment = $payments[0];
                    $matches = DB::table('transactions')->where('status', 'paid')->where('role_id', $attempt->role_id)
                        ->where('payable_type', $attempt->payable_type)->where('payable_id', $attempt->payable_id)
                        ->where('amount', $attempt->amount)->where('created_at', '<=', $payment->created_at)
                        ->where('updated_at', '>=', $payment->created_at)->limit(2)->pluck('id');
                    if ($matches->count() === 1) {
                        DB::table('transactions')->where('id', $attempt->id)->update(['payment_id' => $payment->id]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_callback_issues');
        Schema::dropIfExists('transaction_transitions');
        // MySQL may replace the original foreign-key index with the covering history index.
        if (DB::getDriverName() === 'mysql' && ! Schema::hasIndex('transactions', 'transactions_user_id_foreign')) {
            Schema::table('transactions', fn (Blueprint $table) => $table->index('user_id', 'transactions_user_id_foreign'));
        }
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('transactions_user_history_index');
            $table->dropUnique('transactions_payment_record_unique');
            $table->dropColumn('payment_id');
        });
    }
};
