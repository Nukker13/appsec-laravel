<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flag_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('lab_id')->constrained();
            $table->boolean('is_correct');
            $table->timestamp('created_at')->nullable();

            $table->index(['user_id', 'lab_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flag_attempts');
    }
};
