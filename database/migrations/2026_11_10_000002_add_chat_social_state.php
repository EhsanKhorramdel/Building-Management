<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->constrained('messages')->nullOnDelete();
            $table->foreignId('pinned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('pinned_at')->nullable();
            $table->string('deletion_kind', 20)->nullable();
            $table->unsignedInteger('social_version')->default(0);
            $table->index(['group_id', 'pinned_at'], 'messages_group_pinned_at_index');
        });

        Schema::create('message_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $emoji = $table->string('emoji', 16);
            if (in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true)) {
                $emoji->collation('utf8mb4_bin');
            }
            $table->unique(['message_id', 'user_id', 'emoji'], 'message_reactions_message_user_emoji_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_reactions');
        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex('messages_group_pinned_at_index');
            $table->dropConstrainedForeignId('parent_id');
            $table->dropConstrainedForeignId('pinned_by');
            $table->dropColumn(['pinned_at', 'deletion_kind', 'social_version']);
        });
    }
};
