<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('test_question_options', function (Blueprint $table) {
      $table->id();
      $table->foreignId('question_id')->constrained('test_questions')->cascadeOnDelete();
      $table->text('text');
      $table->unsignedSmallInteger('order')->default(0);
      $table->boolean('is_correct')->default(false);
      $table->timestamps();
      $table->index(['question_id', 'order']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('test_question_options');
  }
};
