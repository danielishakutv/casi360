<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rfqs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('rfq_number')->unique();
            $table->string('title');
            $table->string('pr_reference')->nullable();
            $table->foreignUuid('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->enum('status', ['draft', 'sent', 'received', 'evaluated', 'cancelled'])->default('draft');
            $table->date('issue_date')->nullable();
            $table->date('deadline')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('rfq_number');
            $table->index('status');
            $table->index('vendor_id');
        });

        Schema::create('rfq_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('rfq_id')->constrained('rfqs')->cascadeOnDelete();
            $table->string('description');
            $table->string('unit')->nullable();
            $table->decimal('quantity', 15, 4)->default(0);
            $table->decimal('vendor_unit_price', 15, 2)->nullable();
            $table->decimal('vendor_total', 15, 2)->nullable();
            $table->timestamps();

            $table->index('rfq_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rfq_items');
        Schema::dropIfExists('rfqs');
    }
};
