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
    Schema::create('certification_attendances', function (Blueprint $table) {
      $table->id();

      // Foreign keys
      $table->foreignId('participant_id')->constrained('certification_participants')->cascadeOnDelete();
      $table->foreignId('session_id')->constrained('certification_sessions')->cascadeOnDelete();

      // Attendance details
      $table->enum('status', ['present', 'absent'])->default('absent');
      $table->string('absence_notes')->nullable();

      // Timestamps
      $table->timestamp('recorded_at')->useCurrent();
      $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('certification_attendances');
  }
};
