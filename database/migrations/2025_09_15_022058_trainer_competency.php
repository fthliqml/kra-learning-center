<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('trainer_competency', function (Blueprint $table) {
            $table->id();

            // Foreign keys
            $table->foreignId('trainer_id')->constrained('trainer')->cascadeOnDelete();
            $table->foreignId('competency_id')->constrained('competency')->cascadeOnDelete();

            // Timestamps
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trainer_competency');
    }
};
