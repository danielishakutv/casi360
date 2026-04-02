<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('boqs', function (Blueprint $table) {
            $table->string('department')->nullable()->after('project_code');
            $table->string('category')->nullable()->after('department');
            $table->json('signoffs')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('boqs', function (Blueprint $table) {
            $table->dropColumn(['department', 'category', 'signoffs']);
        });
    }
};
