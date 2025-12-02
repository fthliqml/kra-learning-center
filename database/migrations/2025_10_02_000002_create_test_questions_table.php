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
        Schema::create('test_questions', function (Blueprint $table) {
            $table->id();

            // Foreign keys
            $table->foreignId('test_id')->constrained('tests')->cascadeOnDelete();

            // Question details
            $table->enum('question_type', ['multiple', 'essay']);
            $table->text('text');
            $table->unsignedSmallInteger('order')->default(0);
            $table->unsignedSmallInteger('max_points')->default(1);

            // Timestamps
            $table->timestamps();

            // Indexes
            $table->index(['test_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('test_questions');
    }
};
