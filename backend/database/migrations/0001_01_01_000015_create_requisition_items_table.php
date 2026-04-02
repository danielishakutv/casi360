<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requisition_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('requisition_id')->constrained('requisitions')->onDelete('cascade');
            $table->foreignUuid('inventory_item_id')->nullable()->constrained('inventory_items')->onDelete('set null');
            $table->string('description');
            $table->integer('quantity')->default(1);
            $table->string('unit', 50)->default('pcs');
            $table->decimal('estimated_unit_cost', 15, 2)->default(0);
            $table->decimal('estimated_total_cost', 15, 2)->default(0);
            $table->timestamps();

            $table->index('requisition_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requisition_items');
    }
};
