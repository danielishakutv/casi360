<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('designations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->foreignUuid('department_id')->constrained('departments')->onDelete('cascade');
            $table->enum('level', ['junior', 'mid', 'senior', 'lead', 'executive'])->default('mid');
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->index('title');
            $table->index('level');
            $table->index('status');
            $table->unique(['title', 'department_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('designations');
    }
};
