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
        Schema::create('survey_templates', function (Blueprint $table) {
            $table->id();

            // Details
            $table->string('title');
            $table->text('description');
            $table->enum('status', ['draft', 'active']);
            $table->integer('level');

            // Timestamps
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('survey_templates');
    }
};
