<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Membuat tabel competency (master daftar kompetensi).
     */
    public function up(): void
    {
        Schema::create('competency', function (Blueprint $table) {
            $table->id();

            // Core Fields
            $table->string('description');

            // Meta
            $table->timestamps();
        });
    }

    /**
     * Menghapus tabel competency.
     */
    public function down(): void
    {
        Schema::dropIfExists('competency');
    }
};
