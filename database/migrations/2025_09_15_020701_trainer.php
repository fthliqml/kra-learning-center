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
        Schema::create('trainer', function (Blueprint $table) {
            $table->id();

            // Foreign keys
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            // Details
            $table->string('name')->nullable();
            $table->string('institution');
            $table->string('signature_path')->nullable();

            // Timestamps
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trainer');
    }
};
