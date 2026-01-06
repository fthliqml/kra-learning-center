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
    Schema::create('tests', function (Blueprint $table) {
      $table->id();

      // Foreign keys (one of these must be filled)
      $table->foreignId('course_id')->nullable()->constrained()->cascadeOnDelete();
      $table->foreignId('training_module_id')->nullable()->constrained()->cascadeOnDelete();

      // Test details
      $table->enum('type', ['pretest', 'posttest']);
      $table->unsignedSmallInteger('passing_score')->default(75);
      $table->unsignedSmallInteger('max_attempts')->nullable();
      $table->boolean('randomize_question')->default(false);
      $table->boolean('show_result_immediately')->default(true);

      // Timestamps
      $table->timestamps();

      // Unique constraints
      $table->unique(['course_id', 'type']);
      $table->unique(['training_module_id', 'type']);
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('tests');
  }
};
