<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->string('code', 50)->nullable()->after('name');
        });

        // Backfill the two codes the approval workflow depends on.
        // We match on the beginning of the name (case-insensitive) so variants
        // like "Finance & Admin" or "Procurement Department" still resolve.
        DB::statement("UPDATE departments SET code = 'FINANCE' WHERE code IS NULL AND name LIKE 'Finance%'");
        DB::statement("UPDATE departments SET code = 'PROCUREMENT' WHERE code IS NULL AND name LIKE 'Procurement%'");

        // For everything else, derive a safe uppercase slug from the name so the
        // field isn't blank. Admins can still edit it via the API.
        DB::statement("
            UPDATE departments
            SET code = UPPER(REPLACE(REPLACE(REPLACE(TRIM(name), ' ', '_'), '-', '_'), '&', 'AND'))
            WHERE code IS NULL
        ");

        // Now add the unique index (done AFTER backfill so the UPDATE can't collide)
        Schema::table('departments', function (Blueprint $table) {
            $table->unique('code');
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropColumn('code');
        });
    }
};
