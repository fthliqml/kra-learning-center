<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Membuat tabel user_courses (pivot progres enroll user pada course hasil assignment).
     */
    public function up(): void
    {
        Schema::create('user_courses', function (Blueprint $table) {
            $table->id();

            // Foreign Keys
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignId('assignment_id')->constrained('course_assignments')->cascadeOnDelete();

            // Progress Fields
            $table->integer('current_step');
            $table->enum('status', ['not_started', 'in_progress', 'completed', 'failed'])->default('not_started');

            // Meta
            $table->timestamps();
        });
    }

    /**
     * Menghapus tabel user_courses.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_courses');
    }
};
