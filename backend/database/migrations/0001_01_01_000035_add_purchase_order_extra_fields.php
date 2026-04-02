<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->string('pr_reference')->nullable()->after('notes');
            $table->string('rfq_reference')->nullable()->after('pr_reference');
            $table->string('deliver_name')->nullable()->after('rfq_reference');
            $table->string('deliver_address')->nullable()->after('deliver_name');
            $table->string('deliver_position')->nullable()->after('deliver_address');
            $table->string('deliver_contact')->nullable()->after('deliver_position');
            $table->json('payment_terms')->nullable()->after('deliver_contact');
            $table->text('delivery_terms')->nullable()->after('payment_terms');
            $table->text('remarks')->nullable()->after('delivery_terms');
            $table->decimal('delivery_charges', 15, 2)->default(0)->after('remarks');
            $table->json('signoffs')->nullable()->after('delivery_charges');
        });

        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->string('pr_no')->nullable()->after('total_price');
            $table->string('project_code')->nullable()->after('pr_no');
            $table->string('budget_line')->nullable()->after('project_code');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn([
                'pr_reference', 'rfq_reference',
                'deliver_name', 'deliver_address', 'deliver_position', 'deliver_contact',
                'payment_terms', 'delivery_terms', 'remarks', 'delivery_charges', 'signoffs',
            ]);
        });

        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropColumn(['pr_no', 'project_code', 'budget_line']);
        });
    }
};
