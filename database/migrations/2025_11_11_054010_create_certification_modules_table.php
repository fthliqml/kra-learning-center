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
        Schema::create('certification_modules', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('module_title');
            $table->string('level');
            $table->string('competency');
            $table->enum('group_certification', ['ENGINE', 'MACHINING', 'PPT AND PPM']);
            $table->integer('points_per_module');
            $table->double('new_gex');
            $table->integer('duration');
            $table->text('major_component')->nullable();
            $table->text('mach_model')->nullable();
            $table->double('theory_passing_score');
            $table->double('practical_passing_score');
            $table->boolean('is_active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certification_modules');
    }
};
