<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Membuat tabel pivot trainer_competency (many-to-many antara trainer dan competency).
     */
    public function up(): void
    {
        Schema::create('trainer_competency', function (Blueprint $table) {
            $table->id();

            // Foreign Keys
            $table->foreignId('trainer_id')->constrained('trainer')->cascadeOnDelete();
            $table->foreignId('competency_id')->constrained('competency')->cascadeOnDelete();

            // Meta
            $table->timestamps();
        });
    }

    /**
     * Menghapus tabel trainer_competency.
     */
    public function down(): void
    {
        Schema::dropIfExists('trainer_competency');
    }
};
