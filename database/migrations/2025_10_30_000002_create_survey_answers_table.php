<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    Schema::create('survey_answers', function (Blueprint $table) {
      $table->id();
      $table->foreignId('response_id')->constrained('survey_responses')->onDelete('cascade');
      $table->foreignId('question_id')->constrained('survey_questions')->onDelete('cascade');
      $table->foreignId('selected_option_id')->nullable()->constrained('survey_options')->onDelete('set null');
      $table->text('essay_answer')->nullable();
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
