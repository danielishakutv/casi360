<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disbursements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('purchase_order_id')->constrained('purchase_orders')->onDelete('restrict');
            $table->foreignUuid('disbursed_by')->constrained('users')->onDelete('restrict');
            $table->decimal('amount', 15, 2);
            $table->string('payment_method', 50);
            $table->string('payment_reference')->nullable();
            $table->date('payment_date');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('purchase_order_id');
            $table->index('disbursed_by');
            $table->index('payment_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disbursements');
    }
};
