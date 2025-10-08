<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('test_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_id')->constrained('tests')->cascadeOnDelete();
            $table->enum('question_type', ['multiple', 'essay']);
            $table->text('text');
            $table->unsignedSmallInteger('order')->default(0);
            $table->unsignedSmallInteger('max_points')->default(1);
            $table->timestamps();
            $table->index(['test_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_questions');
    }
};
