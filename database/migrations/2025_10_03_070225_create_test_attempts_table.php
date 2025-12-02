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
        Schema::create('test_attempts', function (Blueprint $table) {
            $table->id();

            // Foreign keys
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('test_id')->constrained('tests')->cascadeOnDelete();

            // Attempt details
            $table->unsignedInteger('attempt_number');
            $table->enum('status', ['started', 'submitted', 'under_review', 'expired'])->default('started');

            // Scoring
            $table->integer('auto_score')->default(0);
            $table->integer('manual_score')->default(0);
            $table->integer('total_score')->default(0);
            $table->boolean('is_passed')->default(false);

            // Timestamps
            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->unique(['user_id', 'test_id', 'attempt_number'], 'test_attempts_user_test_attempt_unique');
            $table->index(['user_id', 'test_id'], 'test_attempts_user_test_idx');
            $table->index(['test_id', 'user_id', 'status'], 'test_attempts_test_user_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('test_attempts');
    }
};
