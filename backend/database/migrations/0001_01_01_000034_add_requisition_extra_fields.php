<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requisitions', function (Blueprint $table) {
            $table->string('delivery_location')->nullable()->after('notes');
            $table->string('purchase_scenario')->nullable()->after('delivery_location');
            $table->boolean('logistics_involved')->default(false)->after('purchase_scenario');
            $table->boolean('boq')->default(false)->after('logistics_involved');
            $table->string('project_code')->nullable()->after('boq');
            $table->string('donor')->nullable()->after('project_code');
            $table->string('currency', 10)->default('NGN')->after('donor');
            $table->decimal('exchange_rate', 15, 4)->nullable()->after('currency');
            $table->json('signoffs')->nullable()->after('exchange_rate');
        });

        Schema::table('requisition_items', function (Blueprint $table) {
            $table->string('project_code')->nullable()->after('estimated_total_cost');
            $table->string('budget_line')->nullable()->after('project_code');
        });
    }

    public function down(): void
    {
        Schema::table('requisitions', function (Blueprint $table) {
            $table->dropColumn([
                'delivery_location', 'purchase_scenario', 'logistics_involved',
                'boq', 'project_code', 'donor', 'currency', 'exchange_rate', 'signoffs',
            ]);
        });

        Schema::table('requisition_items', function (Blueprint $table) {
            $table->dropColumn(['project_code', 'budget_line']);
        });
    }
};
