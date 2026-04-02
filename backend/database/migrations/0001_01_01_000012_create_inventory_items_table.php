<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('sku', 100)->nullable()->unique();
            $table->string('category')->nullable();
            $table->text('description')->nullable();
            $table->string('unit', 50)->default('pcs');
            $table->integer('quantity_in_stock')->default(0);
            $table->integer('reorder_level')->default(0);
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->string('location')->nullable();
            $table->enum('status', ['active', 'inactive', 'out_of_stock'])->default('active');
            $table->timestamps();

            $table->index('name');
            $table->index('sku');
            $table->index('category');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
