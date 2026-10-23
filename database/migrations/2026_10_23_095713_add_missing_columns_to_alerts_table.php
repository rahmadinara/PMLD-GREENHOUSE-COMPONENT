<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alerts', function (Blueprint $table) {
            $table->foreignId('sensor_id')
                ->nullable()
                ->constrained('sensors')
                ->cascadeOnDelete();

            $table->foreignId('reading_id')
                ->nullable()
                ->constrained('readings')
                ->nullOnDelete();

            $table->enum('direction', ['low', 'high'])
                ->default('low');

            $table->text('message')->nullable();

            $table->decimal('value', 12, 2)->nullable();

            $table->decimal('threshold_value', 12, 2)
                ->nullable();

            $table->enum('severity', ['warning', 'critical'])
                ->default('warning');

            $table->enum('status', [
                'open',
                'acknowledged',
                'resolved',
            ])->default('open');

            $table->timestamp('resolved_at')->nullable();

            $table->index([
                'sensor_id',
                'status',
                'created_at',
            ]);

            $table->index([
                'severity',
                'created_at',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('alerts', function (Blueprint $table) {
            $table->dropIndex([
                'sensor_id',
                'status',
                'created_at',
            ]);

            $table->dropIndex([
                'severity',
                'created_at',
            ]);

            $table->dropConstrainedForeignId('reading_id');
            $table->dropConstrainedForeignId('sensor_id');

            $table->dropColumn([
                'direction',
                'message',
                'value',
                'threshold_value',
                'severity',
                'status',
                'resolved_at',
            ]);
        });
    }
};
