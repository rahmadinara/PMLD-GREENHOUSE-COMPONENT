<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HistoricalReadingSeeder extends Seeder
{
    public function run(): void
    {
        $sensorValues = [
            'temperature' => [
                'base' => 29.4,
                'amplitude' => 1.5,
            ],
            'humidity' => [
                'base' => 74,
                'amplitude' => 4,
            ],
            'soil_moisture' => [
                'base' => 29.4,
                'amplitude' => 3,
            ],
            'brightness' => [
                'base' => 164,
                'amplitude' => 40,
            ],
            'water_level' => [
                'base' => 54,
                'amplitude' => 4,
            ],
        ];

        $latestHour = now()->startOfHour();

        foreach ($sensorValues as $type => $config) {
            $sensor = DB::table('sensors')
                ->where('type', $type)
                ->first();

            if (!$sensor) {
                continue;
            }

            $threshold = DB::table('thresholds')
                ->where('sensor_id', $sensor->id)
                ->first();

            if (!$threshold) {
                continue;
            }

            // 24 data, dari 23 jam lalu hingga jam terbaru.
            for ($offset = 23; $offset >= 0; $offset--) {
                $recordedAt = $latestHour->copy()
                    ->subHours($offset);

                $value = round(
                    $config['base']
                    + sin($offset / 3) * $config['amplitude'],
                    2
                );

                $range = $threshold->max_value
                    - $threshold->min_value;

                $margin = $range
                    * $threshold->warning_margin / 100;

                if (
                    $value < $threshold->min_value
                    || $value > $threshold->max_value
                ) {
                    $condition = 'critical';
                } elseif (
                    $value < $threshold->min_value + $margin
                    || $value > $threshold->max_value - $margin
                ) {
                    $condition = 'warning';
                } else {
                    $condition = 'normal';
                }

                DB::table('readings')->updateOrInsert(
                    [
                        'sensor_id' => $sensor->id,
                        'recorded_at' => $recordedAt
                            ->toDateTimeString(),
                    ],
                    [
                        'value' => $value,
                        'condition' => $condition,
                        'updated_at' => now(),
                        'created_at' => $recordedAt,
                    ]
                );
            }
        }
    }
}