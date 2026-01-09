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
        Schema::create('trainings', function (Blueprint $table) {
            $table->id();

            // Foreign keys
            $table->foreignId('course_id')->nullable()->constrained('courses')->nullOnDelete();
            $table->foreignId('module_id')->nullable()->constrained('training_modules')->nullOnDelete();
            $table->foreignId('competency_id')->nullable()->constrained('competency')->nullOnDelete();

            // Details
            $table->string('name');
            $table->enum('type', ['IN', 'OUT', 'LMS']);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->enum('status', ['canceled', 'in_progress', 'done', 'approved', 'rejected'])->default('in_progress');

            // Multi-level approval tracking
            $table->foreignId('section_head_signed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('section_head_signed_at')->nullable();
            $table->foreignId('dept_head_signed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('dept_head_signed_at')->nullable();

            // Timestamps
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trainings');
    }
};
