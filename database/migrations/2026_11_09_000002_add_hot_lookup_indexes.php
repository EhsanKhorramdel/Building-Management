<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEXES = [
        'messages' => ['messages_group_id_id_index', ['group_id', 'id']],
        'user_roles' => ['user_roles_user_complex_index', ['user_id', 'complex_id']],
        'seen_messages' => ['seen_messages_group_user_index', ['group_id', 'user_id']],
    ];

    public function up(): void
    {
        foreach (self::INDEXES as $table => [$name, $columns]) {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->index($columns, $name));
        }
    }

    public function down(): void
    {
        foreach (self::INDEXES as $table => [$name, $columns]) {
            // Restore an FK backing index if InnoDB replaced it with this lookup index.
            if (DB::connection()->getDriverName() === 'mysql' && ! Schema::hasIndex($table, [$columns[0]])) {
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->index($columns[0], $table.'_'.$columns[0].'_foreign'));
            }
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropIndex($name));
        }
    }
};
