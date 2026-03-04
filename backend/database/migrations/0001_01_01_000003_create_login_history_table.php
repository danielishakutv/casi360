<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_history', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->timestamp('login_at');
            $table->timestamp('logout_at')->nullable();
            $table->boolean('login_successful')->default(true);
            $table->string('failure_reason')->nullable();

            // Indexes
            $table->index('user_id');
            $table->index('ip_address');
            $table->index('login_at');
            $table->index('login_successful');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_history');
    }
};
