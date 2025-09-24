<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Membuat tabel training_assesments (catatan nilai & status kelulusan). Ejaan 'assesments' dibiarkan untuk kompatibilitas.
     */
    public function up(): void
    {
        Schema::create('training_assesments', function (Blueprint $table) {
            $table->id();

            // Foreign Keys
            $table->foreignId('training_id')->constrained('trainings')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('users')->cascadeOnDelete();

            // Core Fields
            $table->double('pretest_score')->nullable();
            $table->double('posttest_score')->nullable();
            $table->double('practical_score')->nullable();

            // Status Fields
            $table->enum('status', ['passed', 'failed', 'in_progress'])->default('in_progress');

            // Meta
            $table->timestamps();
        });
    }

    /**
     * Menghapus tabel training_assesments.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_assesments');
    }
};
