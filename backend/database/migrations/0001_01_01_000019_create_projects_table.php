<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('project_code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('objectives')->nullable();
            $table->foreignUuid('department_id')->constrained('departments');
            $table->foreignUuid('project_manager_id')->nullable()->constrained('employees');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('location')->nullable();
            $table->decimal('total_budget', 15, 2)->default(0);
            $table->string('currency', 10)->default('NGN');
            $table->enum('status', ['draft', 'active', 'on_hold', 'completed', 'closed'])->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('project_code');
            $table->index('status');
            $table->index('department_id');
            $table->index('start_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
