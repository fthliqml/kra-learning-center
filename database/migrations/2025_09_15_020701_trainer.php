<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Membuat tabel profil trainer (penyimpan data instruktur eksternal / internal).
     */
    public function up(): void
    {
        Schema::create('trainer', function (Blueprint $table) {
            $table->id();

            // Foreign Keys
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            // Core Fields
            $table->string('name')->nullable();
            $table->string('institution');

            // Meta
            $table->timestamps();
        });
    }

    /**
     * Menghapus tabel trainer.
     */
    public function down(): void
    {
        Schema::dropIfExists('trainer');
    }
};
