<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Morilog\Jalali\Jalalian;

return new class extends Migration
{
    private const UNIQUES = [
        'units' => ['units_complex_number_unique', ['complex_id', 'unit_number']],
        'user_roles' => ['user_roles_complex_unit_user_unique', ['complex_id', 'unit_id', 'user_id']],
        'votes' => ['votes_poll_role_unique', ['poll_id', 'role_id']],
        'seen_messages' => ['seen_messages_message_user_unique', ['message_id', 'user_id']],
        'payment_additional_costs' => ['additional_payments_role_cost_unique', ['role_id', 'incidental_costs_id']],
        'charge_payments' => ['charge_payments_role_charge_period_unique', ['role_id', 'monthly_charge_id', 'period_year', 'period_month']],
    ];

    public function up(): void
    {
        /*
         * monthly_charges.start_date/end_date store Jalali date strings; polls
         * store real datetimes. Payment timestamps are real datetimes too, but
         * these period columns deliberately identify the Jalali payment month.
         */
        Schema::table('charge_payments', function (Blueprint $table) {
            $table->smallInteger('period_year')->nullable();
            $table->tinyInteger('period_month')->nullable();
        });

        DB::transaction(function () {
            DB::table('charge_payments')->orderBy('id')->chunkById(500, function ($payments) {
                foreach ($payments as $payment) {
                    if ($payment->created_at === null) {
                        throw new RuntimeException("Charge payment {$payment->id} has no creation date; restore it before migrating.");
                    }
                    $period = Jalalian::fromCarbon(Carbon::parse($payment->created_at));
                    DB::table('charge_payments')->where('id', $payment->id)->update([
                        'period_year' => $period->getYear(),
                        'period_month' => $period->getMonth(),
                    ]);
                }
            });

            // Preserve dependents before removing duplicate parent IDs.
            $this->deduplicate('units', function ($ids, $keep) {
                DB::table('user_roles')->whereIn('unit_id', $ids)->update(['unit_id' => $keep]);
            });
            $this->deduplicate('user_roles', function ($ids, $keep) {
                foreach (['votes', 'payment_additional_costs', 'charge_payments'] as $table) {
                    DB::table($table)->whereIn('role_id', $ids)->update(['role_id' => $keep]);
                }
            });
            foreach (['votes', 'seen_messages', 'payment_additional_costs', 'charge_payments'] as $table) {
                $this->deduplicate($table);
            }
        });

        Schema::table('charge_payments', function (Blueprint $table) {
            $table->smallInteger('period_year')->nullable(false)->change();
            $table->tinyInteger('period_month')->nullable(false)->change();
        });
        foreach (self::UNIQUES as $table => [$name, $columns]) {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->unique($columns, $name));
        }
    }

    private function deduplicate(string $table, ?Closure $repoint = null): void
    {
        [, $columns] = self::UNIQUES[$table];
        $query = DB::table($table);
        // SQLite and MySQL both allow repeated unique tuples containing NULL.
        foreach ($columns as $column) {
            $query->whereNotNull($column);
        }
        $duplicates = $query->select($columns)->selectRaw('MIN(id) as retained_id')
            ->groupBy($columns)->havingRaw('COUNT(*) > 1')->get();
        $deleted = [];
        foreach ($duplicates as $duplicate) {
            $rows = DB::table($table);
            foreach ($columns as $column) {
                $rows->where($column, $duplicate->$column);
            }
            $ids = $rows->where('id', '<>', $duplicate->retained_id)->orderBy('id')->pluck('id')->all();
            if ($repoint) {
                $repoint($ids, $duplicate->retained_id);
            }
            DB::table($table)->whereIn('id', $ids)->delete();
            $deleted = array_merge($deleted, $ids);
            Log::info('Database duplicate rows removed', [
                'table' => $table, 'retained_id' => $duplicate->retained_id, 'deleted_ids' => $ids,
            ]);
        }
        Log::info('Database duplicate cleanup complete', ['table' => $table, 'deleted_count' => count($deleted), 'deleted_ids' => $deleted]);
    }

    public function down(): void
    {
        foreach (array_reverse(self::UNIQUES, true) as $table => [$name, $columns]) {
            // InnoDB may discard an automatic FK index when a composite index replaces it.
            if (DB::connection()->getDriverName() === 'mysql' && ! Schema::hasIndex($table, [$columns[0]])) {
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->index($columns[0], $table.'_'.$columns[0].'_foreign'));
            }
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropUnique($name));
        }
        Schema::table('charge_payments', fn (Blueprint $table) => $table->dropColumn(['period_year', 'period_month']));
    }
};
