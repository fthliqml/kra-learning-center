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
        Schema::create('training_plan_recoms', function (Blueprint $table) {
            $table->id();

            // Foreign Key
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('competency_id')->nullable()->constrained('competency')->nullOnDelete();
            $table->foreignId('training_module_id')->nullable()->constrained('training_modules')->nullOnDelete();
            $table->foreignId('recommended_by')->nullable()->constrained('users')->nullOnDelete();

            // Main Field
            $table->unsignedSmallInteger('year');
            $table->boolean('is_active')->default(true);

            // Constraints & Indexes
            $table->unique(['user_id', 'year', 'competency_id']);
            $table->unique(['user_id', 'year', 'training_module_id'], 'tpr_user_year_training_module_unique');
            $table->index(['user_id', 'year', 'is_active']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_plan_recoms');
    }
};
