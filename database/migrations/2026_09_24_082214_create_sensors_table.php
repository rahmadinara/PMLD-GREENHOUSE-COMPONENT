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
        Schema::create('sensors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->unique();
            $table->string('unit', 20);
            $table->string('location')->default('Greenhouse FRC');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->index(['status', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sensors');
    }
};
