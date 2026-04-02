<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forums', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['general', 'department'])->default('general');
            $table->foreignUuid('department_id')->nullable()->constrained('departments')->cascadeOnDelete();
            $table->enum('status', ['active', 'archived'])->default('active');
            $table->timestamps();

            $table->index('type');
            $table->index('department_id');
            $table->index('status');
            $table->unique('department_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forums');
    }
};
