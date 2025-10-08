<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('test_attempt_answers', function (Blueprint $table) {
            $table->id();

            // Foreign keys
            $table->foreignId('attempt_id')->constrained('test_attempts')->onDelete('cascade');
            $table->foreignId('question_id')->constrained('test_questions')->onDelete('cascade');
            $table->foreignId('selected_option_id')->nullable()->constrained('test_question_options')->onDelete('cascade');

            // Answer details
            $table->text('essay_answer')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->integer('earned_points')->default(0);

            // Timestamps
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_attempt_answers');
    }
};
