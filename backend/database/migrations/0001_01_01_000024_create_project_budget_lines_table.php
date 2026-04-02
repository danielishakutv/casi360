<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_budget_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignUuid('budget_category_id')->constrained('budget_categories');
            $table->string('description');
            $table->string('unit')->nullable();
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->decimal('total_cost', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('project_id');
            $table->index('budget_category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_budget_lines');
    }
};
