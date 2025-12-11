<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('project_plans', function (Blueprint $table) {
            $table->id();

            // Foreign keys
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('mentor_id')->nullable()->constrained('users')->nullOnDelete();

            // Project plan details
            $table->string('name')->nullable(); // Project name
            $table->text('objective')->nullable();

            // Status & Period
            $table->string('status')->default('draft');
            $table->unsignedSmallInteger('year');

            // SPV Approval (Level 1)
            $table->foreignId('spv_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('spv_approved_at')->nullable();

            // Leader LID Approval (Level 2)
            $table->foreignId('leader_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('leader_approved_at')->nullable();

            // Rejection reason
            $table->text('rejection_reason')->nullable();

            // Timestamps
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_plans');
    }
};
