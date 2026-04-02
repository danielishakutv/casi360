<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('module', 50);
            $table->string('feature', 50);
            $table->string('action', 50);
            $table->string('key', 150)->unique();
            $table->string('description')->nullable();
            $table->timestamps();

            $table->index(['module', 'feature']);
        });

        Schema::create('role_permissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('role', 20);
            $table->foreignUuid('permission_id')->constrained('permissions')->onDelete('cascade');
            $table->boolean('allowed')->default(true);
            $table->timestamps();

            $table->unique(['role', 'permission_id']);
            $table->index('role');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
    }
};
