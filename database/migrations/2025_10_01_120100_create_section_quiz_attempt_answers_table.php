<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('section_quiz_attempt_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_attempt_id')->constrained('section_quiz_attempts')->cascadeOnDelete();
            $table->foreignId('quiz_question_id')->constrained('section_quiz_questions')->cascadeOnDelete();
            $table->foreignId('selected_option_id')->nullable()->constrained('section_quiz_question_options')->nullOnDelete();
            $table->text('answer_text')->nullable(); // for essay
            $table->boolean('is_correct')->nullable(); // null if not evaluated (essay or missing key)
            $table->unsignedSmallInteger('points_awarded')->nullable();
            $table->unsignedSmallInteger('order')->default(0);
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();
            $table->unique(['quiz_attempt_id', 'quiz_question_id'], 'uq_attempt_question');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('section_quiz_attempt_answers');
    }
};
