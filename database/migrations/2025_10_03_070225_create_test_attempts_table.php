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
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('test_id')->constrained('tests')->onDelete('cascade');

            // Attempt details
            $table->unsignedInteger('attempt_number');
            $table->enum('status', ['started', 'submitted', 'under_review', 'expired'])->default('started');

            // Scoring
            $table->integer('auto_score')->default(0);
            $table->integer('manual_score')->default(0);
            $table->integer('total_score')->default(0);
            $table->boolean('is_passed')->default(false);

            // Timestamps
            $table->timestamp('started_at');
            $table->timestamp('submitted_at');
            $table->timestamp('expired_at');
            $table->timestamps();
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
