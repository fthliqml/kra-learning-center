<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    Schema::create('survey_template_defaults', function (Blueprint $table) {
      $table->id();
      $table->foreignId('survey_template_id')->constrained('survey_templates')->onDelete('cascade');
      $table->string('group_comp', 20); // BMC, BC, MMP, LC, MDP, TOC
      $table->integer('level')->default(1); // Survey level (1, 2, 3)
      $table->timestamps();

      // Ensure one template per group_comp per level
      $table->unique(['group_comp', 'level']);
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('survey_template_defaults');
  }
};
