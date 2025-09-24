<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Membuat tabel training_sessions (jadwal per hari untuk sebuah training).
     */
    public function up(): void
    {
        Schema::create('training_sessions', function (Blueprint $table) {
            $table->id();

            // Foreign Keys
            $table->foreignId('training_id')->constrained('trainings')->cascadeOnDelete();
            $table->foreignId('trainer_id')->constrained('trainer')->cascadeOnDelete();

            // Session Details
            $table->string('room_name')->nullable();
            $table->string('room_location')->nullable();
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('day_number');

            // Status Fields
            $table->enum('status', ['cancelled', 'in_progress', 'done'])->default('in_progress');

            // Meta
            $table->timestamps();
        });
    }

    /**
     * Menghapus tabel training_sessions.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_sessions');
    }
};
