<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar_path')->nullable();
            $table->json('notification_preferences')->nullable();
        });
        Schema::table('complexes', function (Blueprint $table) {
            $table->timestamp('join_link_expires_at')->nullable();
            $table->timestamp('join_link_expiry_recorded_at')->nullable();
            $table->unsignedTinyInteger('join_link_uses_left')->nullable();
            $table->foreignId('join_link_issued_by')->nullable()->constrained('users')->nullOnDelete();
        });
        Schema::create('building_preferences', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('complex_id')->constrained()->cascadeOnDelete();
            $table->string('panel')->default('home');
            $table->string('layout')->default('gridView');
            $table->primary(['user_id', 'complex_id']);
        });
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('building_preferences');
        Schema::table('complexes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('join_link_issued_by');
            $table->dropColumn(['join_link_expires_at', 'join_link_expiry_recorded_at', 'join_link_uses_left']);
        });
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['avatar_path', 'notification_preferences']));
    }
};
