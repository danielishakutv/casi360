<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rfqs', function (Blueprint $table) {
            $table->string('project_code')->nullable()->after('pr_reference');
            $table->string('structure')->nullable()->after('project_code');
            $table->string('currency', 10)->default('NGN')->after('structure');
            $table->json('request_types')->nullable()->after('currency');
            // Supplier info
            $table->string('supplier_name')->nullable()->after('vendor_id');
            $table->string('supplier_address')->nullable()->after('supplier_name');
            $table->string('supplier_phone')->nullable()->after('supplier_address');
            $table->string('supplier_email')->nullable()->after('supplier_phone');
            $table->string('contact_person')->nullable()->after('supplier_email');
            // Delivery info
            $table->string('delivery_address')->nullable()->after('deadline');
            $table->date('delivery_date')->nullable()->after('delivery_address');
            $table->text('delivery_terms')->nullable()->after('delivery_date');
            $table->text('payment_terms')->nullable()->after('delivery_terms');
            // Signoffs
            $table->json('signoffs')->nullable()->after('notes');
        });

        Schema::table('rfq_items', function (Blueprint $table) {
            $table->string('item_number')->nullable()->after('rfq_id');
            $table->decimal('unit_cost', 15, 2)->nullable()->after('quantity');
            $table->decimal('total', 15, 2)->nullable()->after('vendor_total');
        });
    }

    public function down(): void
    {
        Schema::table('rfqs', function (Blueprint $table) {
            $table->dropColumn([
                'project_code', 'structure', 'currency', 'request_types',
                'supplier_name', 'supplier_address', 'supplier_phone', 'supplier_email', 'contact_person',
                'delivery_address', 'delivery_date', 'delivery_terms', 'payment_terms',
                'signoffs',
            ]);
        });

        Schema::table('rfq_items', function (Blueprint $table) {
            $table->dropColumn(['item_number', 'unit_cost', 'total']);
        });
    }
};
