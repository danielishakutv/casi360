<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('boqs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('boq_number')->unique();
            $table->string('title');
            $table->string('pr_reference')->nullable();
            $table->string('project_code')->nullable();
            $table->string('prepared_by')->nullable();
            $table->enum('status', ['draft', 'submitted', 'approved', 'revised'])->default('draft');
            $table->date('date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('boq_number');
            $table->index('status');
            $table->index('pr_reference');
        });

        Schema::create('boq_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('boq_id')->constrained('boqs')->cascadeOnDelete();
            $table->string('section')->nullable();
            $table->string('description');
            $table->string('unit')->nullable();
            $table->decimal('quantity', 15, 4)->default(0);
            $table->decimal('unit_rate', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->timestamps();

            $table->index('boq_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('boq_items');
        Schema::dropIfExists('boqs');
    }
};
