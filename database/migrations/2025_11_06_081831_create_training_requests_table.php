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
        Schema::create('training_requests', function (Blueprint $table) {
            $table->id();
            // Foreign Keys
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();

            // Main Fields
            $table->string('name');
            $table->string('section');
            $table->string('competency');
            $table->string('reason');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');

            // Meta
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_requests');
    }
};
