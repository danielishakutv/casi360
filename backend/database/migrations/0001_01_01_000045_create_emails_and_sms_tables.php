<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emails', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('subject');
            $table->text('body');
            $table->string('audience');
            $table->json('recipient_ids')->nullable();
            $table->json('department_ids')->nullable();
            $table->integer('recipient_count')->default(0);
            $table->string('status')->default('queued');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index('sent_by');
            $table->index('status');
        });

        Schema::create('sms_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('message');
            $table->string('audience');
            $table->json('recipient_ids')->nullable();
            $table->json('department_ids')->nullable();
            $table->integer('recipient_count')->default(0);
            $table->string('status')->default('queued');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index('sent_by');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_messages');
        Schema::dropIfExists('emails');
    }
};
