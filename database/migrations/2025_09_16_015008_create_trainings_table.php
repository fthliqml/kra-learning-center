<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Membuat tabel trainings (master event/sesi pelatihan utama).
     */
    public function up(): void
    {
        Schema::create('trainings', function (Blueprint $table) {
            $table->id();

            // Core Fields
            $table->string('name');
            $table->enum('type', ['IN', 'OUT', 'K-LEARN']);
            $table->enum('group_comp', ['BMC', 'BC', 'MMP', 'LC', 'MDP', 'TOC']);
            $table->date('start_date');
            $table->date('end_date')->nullable();

            // Status Fields
            $table->enum('status', ['canceled', 'in_progress', 'done'])->default('in_progress');

            // Meta
            $table->timestamps();
        });
    }

    /**
     * Menghapus tabel trainings.
     */
    public function down(): void
    {
        Schema::dropIfExists('trainings');
    }
};
