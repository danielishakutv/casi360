<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->integer('days_allowed');
            $table->integer('carry_over_max')->nullable();
            $table->boolean('paid')->default(true);
            $table->boolean('requires_approval')->default(true);
            $table->string('status')->default('active');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('holidays', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->date('date');
            $table->string('type')->default('public');
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holidays');
        Schema::dropIfExists('leave_types');
    }
};
