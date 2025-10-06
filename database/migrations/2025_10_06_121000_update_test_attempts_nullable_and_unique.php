<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('test_attempts', function (Blueprint $table) {
            // Make submitted_at and expired_at nullable
            $table->timestamp('submitted_at')->nullable()->change();
            $table->timestamp('expired_at')->nullable()->change();
            // Add unique constraint for attempt_number per user+test
            $table->unique(['user_id', 'test_id', 'attempt_number'], 'test_attempts_user_test_attempt_unique');
        });
    }

    public function down(): void
    {
        Schema::table('test_attempts', function (Blueprint $table) {
            // Revert unique index
            $table->dropUnique('test_attempts_user_test_attempt_unique');
            // Revert nullability (back to not null); beware existing nulls
            $table->timestamp('submitted_at')->nullable(false)->change();
            $table->timestamp('expired_at')->nullable(false)->change();
        });
    }
};
