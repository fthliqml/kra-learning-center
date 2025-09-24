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
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('edited_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('training_id')->constrained('trainings')->cascadeOnDelete();

            // Core Fields
            $table->string('title');
            $table->string('code');
            $table->text('description');
            $table->enum('group_comp', ['BMC', 'BC', 'MMP', 'LC', 'MDP', 'TOC']);
            $table->string('thumbnail_url');
            $table->integer('duration');
            $table->integer('frequency');

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
