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
    Schema::create('certification_participants', function (Blueprint $table) {
      $table->id();

      // Foreign keys
      $table->foreignId('certification_id')->constrained('certifications')->cascadeOnDelete();
      $table->foreignId('employee_id')->constrained('users')->cascadeOnDelete();

      // Status
      $table->enum('final_status', ['pending', 'passed', 'failed'])->default('pending');
      $table->integer('earned_points')->default(0);

      // Timestamps
      $table->timestamp('assigned_at')->useCurrent();
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('certification_participants');
  }
};
