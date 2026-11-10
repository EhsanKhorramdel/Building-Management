<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->constrained('users');
            $table->foreignId('complex_id')->nullable()->constrained('complexes');
            $table->string('event');
            $table->morphs('subject');
            $table->json('meta')->nullable();
            $table->timestamp('created_at');
            $table->index(['complex_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_events');
    }
};
