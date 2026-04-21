<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requisition_approvals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('requisition_id')->constrained('requisitions')->cascadeOnDelete();
            $table->enum('stage', ['budget_holder', 'finance', 'operations']);
            $table->tinyInteger('stage_order')->unsigned();
            $table->string('stage_label', 50);
            $table->enum('status', ['waiting', 'pending', 'approved', 'forwarded', 'rejected', 'revision', 'skipped'])->default('waiting');
            $table->foreignUuid('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_name')->nullable();
            $table->string('actor_position')->nullable();
            $table->text('comments')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->index('requisition_id');
            $table->index('stage');
            $table->index('status');
            $table->index(['requisition_id', 'stage'], 'req_approvals_req_stage_idx');
            $table->index(['requisition_id', 'status'], 'req_approvals_req_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requisition_approvals');
    }
};
