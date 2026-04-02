<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requisitions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('requisition_number')->unique();
            $table->foreignUuid('department_id')->constrained('departments')->onDelete('restrict');
            $table->foreignUuid('requested_by')->constrained('employees')->onDelete('restrict');
            $table->foreignUuid('submitted_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignUuid('purchase_order_id')->nullable()->constrained('purchase_orders')->onDelete('set null');
            $table->string('title');
            $table->text('justification')->nullable();
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->date('needed_by')->nullable();
            $table->decimal('estimated_cost', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->enum('status', ['draft', 'submitted', 'revision', 'pending_approval', 'approved', 'fulfilled', 'cancelled'])->default('draft');
            $table->timestamps();

            $table->index('requisition_number');
            $table->index('status');
            $table->index('priority');
            $table->index('submitted_by');
            $table->index(['department_id', 'status']);
            $table->index(['requested_by', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requisitions');
    }
};
