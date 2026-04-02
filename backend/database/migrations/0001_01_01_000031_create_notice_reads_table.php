<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notice_reads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('notice_id')->constrained('notices')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('read_at');

            $table->unique(['notice_id', 'user_id']);
            $table->index('notice_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notice_reads');
    }
};
