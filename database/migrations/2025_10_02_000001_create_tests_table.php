<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['pretest', 'posttest']);
            // passing_score: treat as percentage 0-100
            $table->unsignedSmallInteger('passing_score');
            // max attempts (nullable => unlimited)
            $table->unsignedSmallInteger('max_attempts')->nullable();
            $table->boolean('randomize_question')->default(false);
            $table->boolean('show_result_immediately')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['course_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tests');
    }
};
