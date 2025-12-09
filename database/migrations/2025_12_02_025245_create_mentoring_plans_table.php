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
        Schema::create('mentoring_plans', function (Blueprint $table) {
            $table->id();

            // Foreign keys
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('mentor_id')->nullable()->constrained('users')->nullOnDelete();

            // Plan Details
            $table->text('objective')->nullable();
            $table->string('method')->nullable();
            $table->integer('frequency')->default(0);
            $table->integer('duration')->default(0);

            // Status & Period
            // Status: draft, pending_spv, rejected_spv, pending_leader, rejected_leader, approved
            $table->string('status')->default('draft');
            $table->unsignedSmallInteger('year');

            // Legacy Approval (kept for backward compatibility)
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

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
        Schema::dropIfExists('mentoring_plans');
    }
};
