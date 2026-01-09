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
        Schema::create('training_modules', function (Blueprint $table) {
            $table->id();

            // Details
            $table->string('title');
            $table->foreignId('competency_id')->nullable()->constrained('competency')->nullOnDelete();
            $table->text('objective');
            $table->text('training_content');
            $table->string('method');
            $table->integer('duration');
            $table->integer('frequency');
            $table->double('theory_passing_score')->default(0);
            // Practical passing score now uses letter grade (A-E)
            $table->enum('practical_passing_score', ['A', 'B', 'C', 'D', 'E'])->default('C');

            // Timestamps
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_modules');
    }
};
