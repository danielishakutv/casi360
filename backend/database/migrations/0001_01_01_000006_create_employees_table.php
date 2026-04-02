<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('staff_id')->unique();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone', 30)->nullable();
            $table->foreignUuid('department_id')->constrained('departments')->onDelete('restrict');
            $table->foreignUuid('designation_id')->constrained('designations')->onDelete('restrict');
            $table->string('manager')->nullable();
            $table->enum('status', ['active', 'on_leave', 'terminated'])->default('active');
            $table->date('join_date');
            $table->date('termination_date')->nullable();
            $table->decimal('salary', 15, 2)->default(0);
            $table->string('avatar')->nullable();
            $table->text('address')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone', 30)->nullable();
            $table->timestamps();

            $table->index('staff_id');
            $table->index('status');
            $table->index('name');
            $table->index(['department_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
