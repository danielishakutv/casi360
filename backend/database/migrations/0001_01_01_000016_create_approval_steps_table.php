<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_steps', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('approvable_type', 50);
            $table->uuid('approvable_id');
            $table->unsignedSmallInteger('step_order');
            $table->string('step_type', 50);
            $table->string('step_label');
            $table->enum('status', ['pending', 'approved', 'rejected', 'skipped'])->default('pending');
            $table->foreignUuid('acted_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('acted_at')->nullable();
            $table->text('comments')->nullable();
            $table->timestamps();

            $table->index(['approvable_type', 'approvable_id'], 'approval_steps_approvable_index');
            $table->index('status');
            $table->index('step_type');
            $table->index('acted_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_steps');
    }
};
