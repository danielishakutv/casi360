<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Staff attendance — daily sign in / sign out.
 *
 * One row per employee per calendar day. Monthly timesheets are derived from
 * these rows (no separate timesheet table / double entry). work_hours is
 * computed on clock-out for fast monthly aggregation.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('date');
            $table->timestamp('clock_in')->nullable();
            $table->timestamp('clock_out')->nullable();
            $table->enum('status', ['present', 'late', 'absent', 'on_leave', 'holiday'])->default('present');
            $table->decimal('work_hours', 6, 2)->nullable();
            $table->string('notes', 500)->nullable();
            // Who recorded/adjusted this row (self sign-in, or HR adjustment).
            $table->foreignUuid('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['employee_id', 'date']);
            $table->index('date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
