<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Unified in-app notification store.
 *
 * One row per recipient per event (e.g. a forum post to 10 people = 10 rows),
 * so each user has their own read state. Powers the top-bar notification bell
 * (forum activity, notices, approval requests/decisions, …). Direct messages
 * keep their own read tracking (messages.read_at) and the dedicated Messages
 * badge, so they are NOT duplicated here.
 *
 * Email delivery (Zeptomail) plugs into the same events later via the existing
 * Notifier service — this table is the in-app half.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete(); // recipient
            $table->string('type', 40);          // forum | notice | approval | requisition | boq | ...
            $table->string('title');
            $table->string('body', 500)->nullable();
            $table->string('url')->nullable();   // frontend deep-link
            $table->json('data')->nullable();    // {entity_id, ...} for future use
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'read_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
