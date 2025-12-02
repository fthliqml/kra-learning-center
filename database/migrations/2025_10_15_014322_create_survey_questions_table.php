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
        Schema::create('survey_questions', function (Blueprint $table) {
            $table->id();

            // Foreign keys
            $table->foreignId('training_survey_id')->constrained('training_surveys')->cascadeOnDelete();

            // Question details
            $table->text('text');
            $table->enum('question_type', ['multiple', 'essay']);
            $table->integer('order');

            // Timestamps
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('survey_questions');
    }
};
