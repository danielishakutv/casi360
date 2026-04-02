<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forum_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('forum_id')->constrained('forums')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->foreignUuid('reply_to_id')->nullable()->constrained('forum_messages')->cascadeOnDelete();
            $table->timestamps();

            $table->index('forum_id');
            $table->index('user_id');
            $table->index('reply_to_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forum_messages');
    }
};
