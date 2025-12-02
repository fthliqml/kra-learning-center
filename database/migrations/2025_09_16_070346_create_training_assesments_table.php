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
        Schema::create('training_assessments', function (Blueprint $table) {
            $table->id();

            // Foreign keys
            $table->foreignId('training_id')->constrained('trainings')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('users')->cascadeOnDelete();

            // Score data
            $table->double('pretest_score')->nullable();
            $table->double('posttest_score')->nullable();
            $table->double('practical_score')->nullable();

            // Status
            $table->enum('status', ['passed', 'failed', 'in_progress'])->default('in_progress');
            $table->string('certificate_path')->nullable();

            // Timestamps
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_assessments');
    }
};
