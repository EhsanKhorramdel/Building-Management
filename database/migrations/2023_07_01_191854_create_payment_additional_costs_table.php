<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payment_additional_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('user_roles');
            $table->foreignId('incidental_costs_id')->constrained('incidental_costs');
            $table->decimal('amount', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_additional_costs');
    }
};
