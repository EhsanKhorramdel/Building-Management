<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Date;


return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('complex_id')->nullable()->constrained('complexes');
            $table->foreignId('unit_id')->nullable()->constrained('units');
            $table->foreignId('user_id')->constrained('users');
            $table->enum('role', ['admin', 'manager', 'resident']);
            $table->date('start_date')->default(Date::now()->format('Y-m-d'));
            $table->date('end_date')->nullable();
            $table->boolean('active')->default('1');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_roles');
    }
};
