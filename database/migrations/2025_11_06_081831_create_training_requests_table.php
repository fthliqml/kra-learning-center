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
        Schema::create('training_requests', function (Blueprint $table) {
            $table->id();

            // Foreign keys
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('competency_id')->constrained('competency')->cascadeOnDelete();

            // Request details
            $table->string('reason');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');

            // Multi-level approval stage:
            // - dept_head          : waiting for Dept Head area terkait
            // - area_division_head : waiting for Division Head area terkait
            // - lid_division_head  : waiting for Division Head LID (final)
            $table->string('approval_stage', 50)->default('dept_head');

            // Timestamps
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_requests');
    }
};
