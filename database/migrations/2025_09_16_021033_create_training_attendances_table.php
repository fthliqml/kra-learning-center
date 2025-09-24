<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Membuat tabel training_attendances (kehadiran peserta per session).
     */
    public function up(): void
    {
        Schema::create('training_attendances', function (Blueprint $table) {
            $table->id();

            // Foreign Keys
            $table->foreignId('session_id')->constrained('training_sessions')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('users')->cascadeOnDelete();

            // Core Fields
            $table->enum('status', ['present', 'absent', 'pending'])->default('pending');
            $table->string('notes')->nullable();
            $table->timestamp('recorded_at')->useCurrent();

            // Meta
            $table->timestamps();
        });
    }

    /**
     * Menghapus tabel training_attendances.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_attendances');
    }
};
