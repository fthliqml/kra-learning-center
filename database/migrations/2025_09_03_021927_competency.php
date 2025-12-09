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
        Schema::create('competency', function (Blueprint $table) {
            $table->id();

            // Details
            $table->string('code')->unique();
            $table->string('name');
            $table->enum('type', ['BMC', 'BC', 'MMP', 'LC', 'MDP', 'TOC']);
            $table->string('description');

            // Timestamps
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('competency');
    }
};
