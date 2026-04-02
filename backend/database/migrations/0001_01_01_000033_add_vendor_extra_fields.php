<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->string('vendor_code')->nullable()->unique()->after('id');
            $table->foreignUuid('category_id')->nullable()->after('name')
                  ->constrained('vendor_categories')->onDelete('set null');
            $table->unsignedTinyInteger('rating')->nullable()->after('bank_account_number');
        });

        // Expand status enum to include 'blacklisted'
        DB::statement("ALTER TABLE vendors MODIFY COLUMN status ENUM('active', 'inactive', 'blacklisted') DEFAULT 'active'");

        // Generate vendor_code for existing vendors
        $vendors = DB::table('vendors')->orderBy('created_at')->get();
        $counter = 1;
        foreach ($vendors as $vendor) {
            DB::table('vendors')->where('id', $vendor->id)->update([
                'vendor_code' => 'VND-' . str_pad($counter++, 3, '0', STR_PAD_LEFT),
            ]);
        }
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE vendors MODIFY COLUMN status ENUM('active', 'inactive') DEFAULT 'active'");

        Schema::table('vendors', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn(['vendor_code', 'category_id', 'rating']);
        });
    }
};
