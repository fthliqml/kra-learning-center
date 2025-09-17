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
        Schema::create('training_sessions', function (Blueprint $table) {
            $table->id();

            // Foreign Keys
            $table->unsignedBigInteger('training_id');
            $table->unsignedBigInteger('instructor_id');

            // Session details
            $table->string('room_name')->nullable();
            $table->string('room_location')->nullable();
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('day_number');

            // Status
            $table->enum('status', ['cancelled', 'in_progress', 'done'])->default('in_progress');

            $table->timestamps();

            // Constraints
            $table->foreign('training_id')->references('id')->on('trainings')->onDelete('cascade');
            // $table->foreign('instructor_id')->references('id')->on('trainers')->onDelete('cascade');
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
