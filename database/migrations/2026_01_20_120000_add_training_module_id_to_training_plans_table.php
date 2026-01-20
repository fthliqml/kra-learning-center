<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('training_plans', 'training_module_id')) {
            return;
        }

        Schema::table('training_plans', function (Blueprint $table) {
            $table
                ->foreignId('training_module_id')
                ->nullable()
                ->after('competency_id')
                ->constrained('training_modules')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('training_plans', 'training_module_id')) {
            return;
        }

        Schema::table('training_plans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('training_module_id');
        });
    }
};
