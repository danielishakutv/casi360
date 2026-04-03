<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('boqs', function (Blueprint $table) {
            $table->string('delivery_location')->nullable()->after('department');
        });

        Schema::table('boq_items', function (Blueprint $table) {
            $table->string('comment')->nullable()->after('total');
        });
    }

    public function down(): void
    {
        Schema::table('boqs', function (Blueprint $table) {
            $table->dropColumn('delivery_location');
        });

        Schema::table('boq_items', function (Blueprint $table) {
            $table->dropColumn('comment');
        });
    }
};
