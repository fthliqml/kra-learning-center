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
        Schema::table('certification_participants', function (Blueprint $table) {
            $table->enum('final_status', ['pending', 'passed', 'failed'])->default('pending')->after('employee_id');
            $table->integer('earned_points')->default(0)->after('final_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('certification_participants', function (Blueprint $table) {
            $table->dropColumn(['final_status', 'earned_points']);
        });
    }
};
