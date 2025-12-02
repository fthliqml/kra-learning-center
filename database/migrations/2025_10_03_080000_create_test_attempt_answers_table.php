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
        Schema::create('test_attempt_answers', function (Blueprint $table) {
            $table->id();

            // Foreign keys
            $table->foreignId('attempt_id')->constrained('test_attempts')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('test_questions')->cascadeOnDelete();
            $table->foreignId('selected_option_id')->nullable()->constrained('test_question_options')->cascadeOnDelete();

            // Answer details
            $table->text('essay_answer')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->integer('earned_points')->default(0);

            // Timestamps
            $table->timestamps();

            // Indexes
            $table->index('attempt_id', 'test_attempt_answers_attempt_idx');
            $table->index('question_id', 'test_attempt_answers_question_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('test_attempt_answers');
    }
};
