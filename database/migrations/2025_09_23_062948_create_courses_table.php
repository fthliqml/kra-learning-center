<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Membuat tabel courses (konten pembelajaran turunan dari training).
     */
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();

            // Foreign Keys (auditing & linkage)
            $table->foreignId('training_id')->constrained('trainings')->cascadeOnDelete();

            // Core Fields
            $table->string('title')->nullable();
            $table->text('description');
            $table->string('thumbnail_url');

            // Status Fields
            $table->enum('status', ['active', 'inactive']);

            // Meta
            $table->timestamps();
        });
    }

    /**
     * Menghapus tabel courses.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
