<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('section_quiz_questions', function (Blueprint $table) {
      $table->id();
      $table->foreignId('section_id')->constrained('sections')->cascadeOnDelete();
      $table->enum('type', ['multiple', 'essay']);
      $table->text('question');
      $table->unsignedInteger('order')->default(0);
      $table->timestamps();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('section_quiz_questions');
  }
};
