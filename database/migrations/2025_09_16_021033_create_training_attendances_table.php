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
        Schema::create('training_attendances', function (Blueprint $table) {
            $table->id();

            // Foreign Keys
            $table->unsignedBigInteger('session_id');
            $table->unsignedBigInteger('employee_id');

            // Attendance data
            $table->enum('status', ['present', 'absent', 'pending'])->default('pending');
            $table->string('notes')->nullable();
            $table->timestamp('recorded_at')->useCurrent();

            $table->timestamps();

            // Constraints
            $table->foreign('session_id')->references('id')->on('training_sessions')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_attendances');
    }
};
