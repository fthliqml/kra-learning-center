<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Membuat tabel course_assignments (penugasan course ke peserta melalui session & trainer).
     */
    public function up(): void
    {
        Schema::create('course_assignments', function (Blueprint $table) {
            $table->id();

            // Foreign Keys
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignId('trainer_id')->constrained('trainer')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('training_session_id')->constrained('training_sessions')->cascadeOnDelete();

            // Core Fields
            $table->timestamp('assigned_at')->nullable();

            // Meta
            $table->timestamps();
        });
    }

    /**
     * Menghapus tabel course_assignments.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_assignments');
    }
};
