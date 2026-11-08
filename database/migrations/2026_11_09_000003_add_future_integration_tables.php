<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('complexes', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
        });
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->nullableMorphs('payable', 'transactions_payable_index');
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->foreignId('role_id')->nullable()->constrained('user_roles');
            $table->decimal('amount', 12, 2);
            $table->string('gateway')->nullable();
            $table->string('authority')->nullable();
            $table->string('reference_id')->nullable();
            $table->string('status')->default('pending');
            $table->json('meta')->nullable();
            $table->timestamps();
        });
        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();
            $table->string('recipient');
            $table->string('template')->nullable();
            $table->text('body')->nullable();
            $table->string('status')->default('logged');
            $table->string('driver')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_logs');
        Schema::dropIfExists('transactions');
        Schema::table('complexes', fn (Blueprint $table) => $table->dropColumn(['latitude', 'longitude']));
    }
};
