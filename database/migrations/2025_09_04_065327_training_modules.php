<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Membuat tabel training_modules (template materi pelatihan generik, bukan instance).
     */
    public function up(): void
    {
        Schema::create('training_modules', function (Blueprint $table) {
            $table->id();

            // Core Fields
            $table->string('title');
            $table->enum('group_comp', ['BMC', 'BC', 'MMP', 'LC', 'MDP', 'TOC']);
            $table->text('objective');
            $table->text('training_content');
            $table->string('method');
            $table->integer('duration');
            $table->integer('frequency');

            // Meta
            $table->timestamps();
        });
    }

    /**
     * Menghapus tabel training_modules.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_modules');
    }
};
