<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rfps', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('rfp_number')->unique();
            $table->string('po_reference')->nullable();
            $table->string('grn_reference')->nullable();
            $table->foreignUuid('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->enum('status', ['draft', 'submitted', 'approved', 'paid', 'rejected'])->default('draft');
            $table->date('payment_date')->nullable();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('tax_rate', 8, 4)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->enum('payment_method', ['bank_transfer', 'cash', 'cheque'])->nullable();
            $table->text('bank_details')->nullable();
            $table->text('notes')->nullable();
            $table->json('signoffs')->nullable();
            $table->timestamps();

            $table->index('rfp_number');
            $table->index('status');
            $table->index('vendor_id');
            $table->index('po_reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rfps');
    }
};
