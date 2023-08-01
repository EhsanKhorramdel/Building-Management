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
        Schema::create('incidental_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('complex_id')->constrained('complexes');
            $table->string('cost_invoice')->nullable();
            $table->string('cost_explanation')->nullable();
            $table->string('title');
            $table->decimal('total_amount', 10, 2);
            $table->decimal('share_amount', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incidental_costs');
    }
};
