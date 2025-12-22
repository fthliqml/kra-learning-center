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
    Schema::create('instructor_daily_records', function (Blueprint $table) {
      $table->id();
      $table->foreignId('instructor_id')->constrained('users')->onDelete('cascade');
      $table->date('date');
      $table->string('code')->unique(); // Auto-generated: IDR-00001
      $table->enum('group', ['JAI', 'JAO']);
      $table->text('activity');
      $table->text('remarks')->nullable();
      $table->decimal('hour', 4, 1); // e.g., 8.5 hours
      $table->timestamps();

      // Index for faster queries
      $table->index(['instructor_id', 'date']);
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('instructor_daily_records');
  }
};
