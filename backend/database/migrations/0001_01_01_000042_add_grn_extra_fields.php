<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grns', function (Blueprint $table) {
            $table->string('office')->nullable()->after('vendor_id');
        });

        Schema::table('grn_items', function (Blueprint $table) {
            $table->string('quality_status')->nullable()->default('good')->after('received_qty');
        });
    }

    public function down(): void
    {
        Schema::table('grns', function (Blueprint $table) {
            $table->dropColumn('office');
        });

        Schema::table('grn_items', function (Blueprint $table) {
            $table->dropColumn('quality_status');
        });
    }
};
