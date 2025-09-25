<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Membuat tabel learning_modules (unit konten per course).
     */
    public function up(): void
    {
        Schema::create('learning_modules', function (Blueprint $table) {
            $table->id();

            // Foreign Keys
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();

            // Core Fields
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->enum('content_type', ['video', 'pdf']);
            $table->text('url');
            $table->boolean('is_completed');

            // Meta
            $table->timestamps();
        });
    }

    /**
     * Menghapus tabel learning_modules.
     */
    public function down(): void
    {
        Schema::dropIfExists('learning_modules');
    }
};
