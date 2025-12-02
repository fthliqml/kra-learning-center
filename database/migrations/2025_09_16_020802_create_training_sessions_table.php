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
        Schema::create('training_sessions', function (Blueprint $table) {
            $table->id();

            // Foreign keys
            $table->foreignId('training_id')->constrained('trainings')->cascadeOnDelete();
            $table->foreignId('trainer_id')->nullable()->constrained('trainer')->nullOnDelete();

            // Session details
            $table->string('room_name')->nullable();
            $table->string('room_location')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->date('date');
            $table->integer('day_number');
            $table->enum('status', ['cancelled', 'in_progress', 'done'])->default('in_progress');

            // Timestamps
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_sessions');
    }
};
