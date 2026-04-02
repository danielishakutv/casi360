<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add extra header fields to rfps
        Schema::table('rfps', function (Blueprint $table) {
            $table->string('project_code')->nullable()->after('grn_reference');
            $table->string('payee')->nullable()->after('vendor_id');
            $table->string('currency', 10)->default('NGN')->after('payee');
            $table->decimal('exchange_rate', 15, 4)->nullable()->after('currency');
            $table->string('department')->nullable()->after('exchange_rate');
            $table->string('budget_line')->nullable()->after('department');
            $table->date('date')->nullable()->after('budget_line');
            $table->json('supporting_docs')->nullable()->after('signoffs');
        });

        // Create rfp_items table
        Schema::create('rfp_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('rfp_id')->constrained('rfps')->cascadeOnDelete();
            $table->string('description');
            $table->string('project_code')->nullable();
            $table->string('budget_line')->nullable();
            $table->integer('quantity')->default(1);
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->string('dept')->nullable();
            $table->decimal('total', 15, 2)->default(0);
            $table->timestamps();

            $table->index('rfp_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rfp_items');

        Schema::table('rfps', function (Blueprint $table) {
            $table->dropColumn([
                'project_code', 'payee', 'currency', 'exchange_rate',
                'department', 'budget_line', 'date', 'supporting_docs',
            ]);
        });
    }
};
