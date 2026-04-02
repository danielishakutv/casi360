<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('employee_id')->constrained('employees')->onDelete('cascade');
            $table->string('title');
            $table->text('content');
            $table->enum('type', ['general', 'performance', 'disciplinary', 'commendation', 'medical', 'training'])->default('general');
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            $table->foreignUuid('created_by')->constrained('users')->onDelete('restrict');
            $table->timestamps();

            $table->index('employee_id');
            $table->index('type');
            $table->index('priority');
            $table->index('created_by');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notes');
    }
};
