<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds currency tracking to BOQs.
 *
 * Budgets are prepared in US Dollars at a fixed exchange rate; the Naira
 * value is derived for display from that rate. So a BOQ now stores:
 *   - currency       : the document currency (defaults to 'USD')
 *   - exchange_rate  : USD -> NGN rate the budget was prepared at, used to
 *                      show the Naira equivalent (item amounts stay in USD)
 *
 * Additive and nullable — existing BOQs are unaffected (they default to USD
 * with no rate until edited).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('boqs', function (Blueprint $table) {
            $table->string('currency', 10)->default('USD')->after('category');
            $table->decimal('exchange_rate', 15, 4)->nullable()->after('currency');
        });
    }

    public function down(): void
    {
        Schema::table('boqs', function (Blueprint $table) {
            $table->dropColumn(['currency', 'exchange_rate']);
        });
    }
};
