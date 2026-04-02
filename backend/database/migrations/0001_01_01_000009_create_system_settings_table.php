<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('group', 50)->index();           // e.g. organization, localization, appearance, system
            $table->string('key', 100)->unique();            // unique setting key
            $table->text('value')->nullable();               // stored as string, cast by type
            $table->string('type', 20)->default('string');   // string, boolean, integer, json, text
            $table->string('label', 150);                    // human-readable label
            $table->string('description', 500)->nullable();
            $table->boolean('is_public')->default(false);    // accessible without authentication
            $table->timestamps();

            $table->index('is_public');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
