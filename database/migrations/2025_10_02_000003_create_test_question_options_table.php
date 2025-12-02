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
    Schema::create('test_question_options', function (Blueprint $table) {
      $table->id();

      // Foreign keys
      $table->foreignId('question_id')->constrained('test_questions')->cascadeOnDelete();

      // Option details
      $table->text('text');
      $table->unsignedSmallInteger('order')->default(0);
      $table->boolean('is_correct')->default(false);

      // Timestamps
      $table->timestamps();

      // Indexes
      $table->index(['question_id', 'order']);
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('test_question_options');
  }
};
