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
        Schema::create('competency_matrixs', function (Blueprint $table) {
            $table->id();

            // Foreign keys
            $table->foreignId('competency_id')->constrained('competency')->cascadeOnDelete();
            $table->foreignId('employees_trained_id')->constrained('users')->cascadeOnDelete();

            // Timestamps
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('competency_matrixs');
    }
};
