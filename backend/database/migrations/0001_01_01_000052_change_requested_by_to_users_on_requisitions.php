<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the old FK constraint pointing to employees
        Schema::table('requisitions', function (Blueprint $table) {
            $table->dropForeign(['requested_by']);
        });

        // Re-add FK pointing to users (requester is always the auth user)
        Schema::table('requisitions', function (Blueprint $table) {
            $table->foreign('requested_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('requisitions', function (Blueprint $table) {
            $table->dropForeign(['requested_by']);
        });

        Schema::table('requisitions', function (Blueprint $table) {
            $table->foreign('requested_by')
                  ->references('id')
                  ->on('employees')
                  ->onDelete('restrict');
        });
    }
};
