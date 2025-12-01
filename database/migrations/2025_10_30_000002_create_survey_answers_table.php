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
    Schema::create('survey_answers', function (Blueprint $table) {
      $table->id();

      // Foreign keys
      $table->foreignId('response_id')->constrained('survey_responses')->cascadeOnDelete();
      $table->foreignId('question_id')->constrained('survey_questions')->cascadeOnDelete();
      $table->foreignId('selected_option_id')->nullable()->constrained('survey_options')->nullOnDelete();

      // Answer details
      $table->text('essay_answer')->nullable();

      // Timestamps
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('survey_answers');
  }
};
