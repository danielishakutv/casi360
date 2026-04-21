<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add 'rejected' to the status enum and a 'date' column for the PR date
        DB::statement("ALTER TABLE requisitions MODIFY COLUMN status ENUM('draft','submitted','revision','pending_approval','approved','rejected','fulfilled','cancelled') NOT NULL DEFAULT 'draft'");

        Schema::table('requisitions', function (Blueprint $table) {
            $table->date('date')->nullable()->after('title');
        });
    }

    public function down(): void
    {
        // Revert status enum (any rows with 'rejected' must be handled before rollback)
        DB::statement("ALTER TABLE requisitions MODIFY COLUMN status ENUM('draft','submitted','revision','pending_approval','approved','fulfilled','cancelled') NOT NULL DEFAULT 'draft'");

        Schema::table('requisitions', function (Blueprint $table) {
            $table->dropColumn('date');
        });
    }
};
