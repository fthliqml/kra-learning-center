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
    Schema::create('training_assessments', function (Blueprint $table) {
      $table->id();

      // Foreign Keys
      $table->unsignedBigInteger('training_id');
      $table->unsignedBigInteger('employee_id');

      // Attendance data
      $table->double('pretest_score')->nullable();
      $table->double('posttest_score')->nullable();
      $table->double('practical_score')->nullable();
      $table->enum('status', ['passed', 'failed', "in_progress"])->default('in_progress');

      $table->timestamps();

      // Constraints
      $table->foreign('training_id')->references('id')->on('trainings')->onDelete('cascade');
      $table->foreign('employee_id')->references('id')->on('users')->onDelete('cascade');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('training_assessments');
  }
};
