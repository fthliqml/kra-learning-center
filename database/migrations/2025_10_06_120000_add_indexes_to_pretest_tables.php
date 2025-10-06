<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('test_attempts', function (Blueprint $table) {
            // Speed up lookups for attempts per user+test and status checks
            $table->index(['user_id', 'test_id'], 'test_attempts_user_test_idx');
            $table->index(['test_id', 'user_id', 'status'], 'test_attempts_test_user_status_idx');
        });

        Schema::table('test_attempt_answers', function (Blueprint $table) {
            // Explicit indexes to speed answer fetches (often added implicitly, made explicit here)
            $table->index('attempt_id', 'test_attempt_answers_attempt_idx');
            $table->index('question_id', 'test_attempt_answers_question_idx');
        });
    }

    public function down(): void
    {
        Schema::table('test_attempts', function (Blueprint $table) {
            $table->dropIndex('test_attempts_user_test_idx');
            $table->dropIndex('test_attempts_test_user_status_idx');
        });

        Schema::table('test_attempt_answers', function (Blueprint $table) {
            $table->dropIndex('test_attempt_answers_attempt_idx');
            $table->dropIndex('test_attempt_answers_question_idx');
        });
    }
};
