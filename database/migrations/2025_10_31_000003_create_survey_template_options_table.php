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
        Schema::create('survey_template_options', function (Blueprint $table) {
            $table->id();

            // Foreign keys
            $table->foreignId('survey_template_question_id')->constrained('survey_template_questions')->cascadeOnDelete();

            // Option details
            $table->string('text');
            $table->integer('order')->default(0);

            // Timestamps
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('survey_template_options');
    }
};
