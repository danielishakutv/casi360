<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('boqs')) {
            return;
        }

        DB::statement("ALTER TABLE boqs MODIFY COLUMN status ENUM('draft','submitted','approved','revised','rejected') NOT NULL DEFAULT 'draft'");
    }

    public function down(): void
    {
        if (!Schema::hasTable('boqs')) {
            return;
        }

        // Anything currently 'rejected' collapses back to 'revised' so the
        // narrower enum won't reject existing rows.
        DB::statement("UPDATE boqs SET status = 'revised' WHERE status = 'rejected'");
        DB::statement("ALTER TABLE boqs MODIFY COLUMN status ENUM('draft','submitted','approved','revised') NOT NULL DEFAULT 'draft'");
    }
};
