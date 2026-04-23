<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('boq_audit_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->char('boq_id', 36);
            $table->char('actor_id', 36);
            $table->string('actor_name')->nullable();
            $table->string('action', 50); // created, updated, submitted, approved, revision, rejected
            $table->text('comments')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('boq_id')->references('id')->on('boqs')->onDelete('cascade');
            $table->foreign('actor_id')->references('id')->on('users');

            $table->index('boq_id');
            $table->index('action');
            $table->index(['boq_id', 'created_at'], 'bal_boq_time_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('boq_audit_logs');
    }
};
