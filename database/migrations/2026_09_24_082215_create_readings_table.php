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
        Schema::create('readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sensor_id')
                ->constrained('sensors')
                ->cascadeOnDelete();
            $table->decimal('value');
            $table->enum('condition',['normal', 'warning', 'critical'])->default('normal');
            $table->timestamp('recorded_at');
            $table->timestamps();
                $table->index(['sensor_id', 'recorded_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('readings');
    }
};
