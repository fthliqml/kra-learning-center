<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('survey_template_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_template_question_id')->constrained('survey_template_questions')->onDelete('cascade');
            $table->string('text');
            $table->integer('order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_template_options');
    }
};
