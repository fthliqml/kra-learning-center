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
        Schema::create('trainings', function (Blueprint $table) {
            $table->id();

            // Foreign keys
            $table->foreignId('course_id')->nullable()->constrained('courses')->nullOnDelete();

            // Details
            $table->string('name');
            $table->enum('type', ['IN', 'OUT', 'LMS']);
            $table->enum('group_comp', ['BMC', 'BC', 'MMP', 'LC', 'MDP', 'TOC']);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->enum('status', ['canceled', 'in_progress', 'done', 'approved', 'rejected'])->default('in_progress');

            // Timestamps
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trainings');
    }
};
