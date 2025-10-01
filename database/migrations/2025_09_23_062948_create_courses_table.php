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

            // Core Fields
            $table->string('title');
            $table->text('description');
            $table->string('thumbnail_url');
            $table->enum('group_comp', ['BMC', 'BC', 'MMP', 'LC', 'MDP', 'TOC']);

            // Status Fields
            $table->enum('status', ['draft', 'inactive', 'assigned']);

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
