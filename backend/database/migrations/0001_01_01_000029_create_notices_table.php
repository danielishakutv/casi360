<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('author_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('body');
            $table->enum('priority', ['normal', 'important', 'critical'])->default('normal');
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->date('publish_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->boolean('is_pinned')->default(false);
            $table->timestamps();

            $table->index('status');
            $table->index('priority');
            $table->index('publish_date');
            $table->index('is_pinned');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notices');
    }
};
