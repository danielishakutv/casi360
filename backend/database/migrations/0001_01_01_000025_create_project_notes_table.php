<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_notes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignUuid('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('content')->nullable();
            $table->string('link_url')->nullable();
            $table->string('link_label')->nullable();
            $table->timestamps();

            $table->index('project_id');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_notes');
    }
};
