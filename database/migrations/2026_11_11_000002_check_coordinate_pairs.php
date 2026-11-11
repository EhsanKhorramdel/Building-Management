<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const TABLES = ['complexes', 'membership_requests'];

    public function up(): void
    {
        $valid = '(latitude IS NULL AND longitude IS NULL) OR (latitude IS NOT NULL AND longitude IS NOT NULL AND latitude BETWEEN -90 AND 90 AND longitude BETWEEN -180 AND 180)';
        foreach (self::TABLES as $table) {
            if (DB::table($table)->whereRaw("NOT ($valid)")->exists()) {
                throw new RuntimeException("Invalid coordinates in $table. Review the affected rows before migrating.");
            }
        }

        foreach (self::TABLES as $table) {
            if (DB::getDriverName() === 'sqlite') {
                // SQLite can validate an added virtual column without rebuilding the table.
                DB::statement("ALTER TABLE $table ADD COLUMN coordinates_valid INTEGER GENERATED ALWAYS AS (CASE WHEN $valid THEN 1 ELSE 0 END) VIRTUAL CHECK (coordinates_valid = 1)");
            } else {
                DB::statement("ALTER TABLE $table ADD CONSTRAINT {$table}_coordinates_valid CHECK ($valid)");
            }
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            if (DB::getDriverName() === 'sqlite') {
                DB::statement("ALTER TABLE $table DROP COLUMN coordinates_valid");
            } else {
                DB::statement("ALTER TABLE $table DROP CHECK {$table}_coordinates_valid");
            }
        }
    }
};
