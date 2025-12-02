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
        Schema::create('section_quiz_question_options', function (Blueprint $table) {
            $table->id();

            // Foreign keys
            $table->foreignId('question_id')->constrained('section_quiz_questions')->cascadeOnDelete();

            // Option details
            $table->string('option');
            $table->boolean('is_correct')->default(false);
            $table->unsignedInteger('order')->default(0);

            // Timestamps
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('section_quiz_question_options');
    }
};
