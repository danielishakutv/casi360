<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('grn_number')->unique();
            $table->string('po_reference')->nullable();
            $table->foreignUuid('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->string('received_by')->nullable();
            $table->enum('status', ['draft', 'inspected', 'accepted', 'rejected', 'partial'])->default('draft');
            $table->date('received_date')->nullable();
            $table->string('delivery_note_no')->nullable();
            $table->text('notes')->nullable();
            $table->json('signoffs')->nullable();
            $table->timestamps();

            $table->index('grn_number');
            $table->index('status');
            $table->index('vendor_id');
            $table->index('po_reference');
        });

        Schema::create('grn_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('grn_id')->constrained('grns')->cascadeOnDelete();
            $table->string('description');
            $table->integer('ordered_qty')->default(0);
            $table->integer('received_qty')->default(0);
            $table->integer('accepted_qty')->default(0);
            $table->integer('rejected_qty')->default(0);
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index('grn_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grn_items');
        Schema::dropIfExists('grns');
    }
};
