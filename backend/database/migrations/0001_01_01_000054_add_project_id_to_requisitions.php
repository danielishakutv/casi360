<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requisitions', function (Blueprint $table) {
            $table->uuid('project_id')->nullable()->after('purchase_order_id');
            $table->foreign('project_id')->references('id')->on('projects')->nullOnDelete();
            $table->index('project_id');
        });

        // Backfill project_id from existing project_code matches
        DB::statement("
            UPDATE requisitions r
            INNER JOIN projects p ON p.project_code = r.project_code
            SET r.project_id = p.id
            WHERE r.project_code IS NOT NULL
              AND r.project_code <> ''
              AND r.project_id IS NULL
        ");
    }

    public function down(): void
    {
        Schema::table('requisitions', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropIndex(['project_id']);
            $table->dropColumn('project_id');
        });
    }
};
