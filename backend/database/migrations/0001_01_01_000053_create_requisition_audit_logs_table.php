<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requisition_audit_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->char('requisition_id', 36)->index();
            $table->char('actor_id', 36);
            $table->string('actor_name')->nullable();
            $table->string('action', 50);       // created, updated, submitted, approved, forwarded, revision, rejected, cancelled
            $table->string('from_status', 50)->nullable();
            $table->string('to_status', 50)->nullable();
            $table->string('stage', 50)->nullable();  // budget_holder | finance | operations | null
            $table->text('comments')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('requisition_id')->references('id')->on('requisitions')->onDelete('cascade');
            $table->foreign('actor_id')->references('id')->on('users');

            $table->index(['requisition_id', 'created_at'], 'ral_req_time_idx');
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requisition_audit_logs');
    }
};
