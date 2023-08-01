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
        Schema::create('complexes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('address');
            $table->string('location')->nullable();
            $table->integer('number_of_floors');
            $table->integer('number_of_units');
            $table->string('join_link')->unique();
            $table->timestamps();
        });
    }
    // 2023_07_01_191600_create_complexes_ta

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('complexes');
    }
};
