<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('po_number')->unique();
            $table->foreignUuid('vendor_id')->constrained('vendors')->onDelete('restrict');
            $table->foreignUuid('department_id')->constrained('departments')->onDelete('restrict');
            $table->foreignUuid('requested_by')->constrained('employees')->onDelete('restrict');
            $table->foreignUuid('submitted_by')->nullable()->constrained('users')->onDelete('set null');
            $table->date('order_date');
            $table->date('expected_delivery_date')->nullable();
            $table->date('actual_delivery_date')->nullable();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->string('currency', 10)->default('NGN');
            $table->text('notes')->nullable();
            $table->enum('status', ['draft', 'submitted', 'revision', 'pending_approval', 'approved', 'ordered', 'partially_received', 'received', 'disbursed', 'cancelled'])->default('draft');
            $table->enum('payment_status', ['unpaid', 'partially_paid', 'paid'])->default('unpaid');
            $table->timestamps();

            $table->index('po_number');
            $table->index('status');
            $table->index('payment_status');
            $table->index('order_date');
            $table->index('submitted_by');
            $table->index(['vendor_id', 'status']);
            $table->index(['department_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
