<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notice_audiences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('notice_id')->constrained('notices')->cascadeOnDelete();
            $table->enum('audience_type', ['all', 'department', 'role']);
            $table->uuid('audience_id')->nullable();
            $table->string('audience_role')->nullable();
            $table->timestamps();

            $table->index('notice_id');
            $table->index('audience_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notice_audiences');
    }
};
