<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HistoricalReadingSeeder extends Seeder
{
    public function run(): void
    {
        $sensors = DB::table('sensors')->get();

        $readings = [];

        $baseTime = now()->startOfHour();

        foreach ($sensors as $sensor) {
            $threshold = DB::table('thresholds')
                ->where('sensor_id', $sensor->id)
                ->first();

            if (!$threshold) {
                continue;
            }

            for ($i = 23; $i >= 0; $i--) {
                $value = $this->generateValue($sensor->type);

                $condition = $this->determineCondition(
                    $value,
                    $threshold
                );

                $recordedAt = $baseTime->copy()->subHours($i);

                $readings[] = [
                    'sensor_id' => $sensor->id,
                    'value' => $value,
                    'condition' => $condition,
                    'recorded_at' => $recordedAt,
                    'created_at' => $recordedAt,
                    'updated_at' => $recordedAt,
                ];
            }
        }

        if (!empty($readings)) {
            DB::table('readings')->insert($readings);
        }
    }

    private function generateValue(string $type): float
    {
        return match ($type) {
            'temperature' => random_int(2000, 3800) / 100,
            'humidity' => random_int(3500, 9500) / 100,
            'soil_moisture' => random_int(1500, 7000) / 100,
            'brightness' => random_int(100000, 1000000) / 100,
            'water_level' => random_int(2000, 10000) / 100,
            default => 0.0,
        };
    }

    private function determineCondition(
        float $value,
        object $threshold
    ): string {
        if (
            $value < (float) $threshold->min_value
            || (
                $threshold->max_value !== null
                && $value > (float) $threshold->max_value
            )
        ) {
            return 'critical';
        }

        if (
            (
                $threshold->warning_min !== null
                && $value < (float) $threshold->warning_min
            )
            || (
                $threshold->warning_max !== null
                && $value > (float) $threshold->warning_max
            )
        ) {
            return 'warning';
        }

        return 'normal';
    }
}
