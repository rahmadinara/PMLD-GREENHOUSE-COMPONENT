<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ThresholdSeeder extends Seeder
{
    public function run(): void
    {
        $thresholds = [
            'temperature' => [
                'min_value' => 25,
                'warning_min' => 27,
                'warning_max' => 33,
                'max_value' => 35,
            ],
            'humidity' => [
                'min_value' => 50,
                'warning_min' => 60,
                'warning_max' => 70,
                'max_value' => 80,
            ],
            'soil_moisture' => [
                'min_value' => 30,
                'warning_min' => 40,
                'warning_max' => 50,
                'max_value' => 60,
            ],
            'brightness' => [
                'min_value' => 2000,
                'warning_min' => 2500,
                'warning_max' => null,
                'max_value' => null,
            ],
            'water_level' => [
                'min_value' => 50,
                'warning_min' => null,
                'warning_max' => null,
                'max_value' => null,
            ],
        ];

        foreach ($thresholds as $type => $data) {
            $sensor = DB::table('sensors')
                ->where('type', $type)
                ->first();

            if (!$sensor) {
                continue;
            }

            DB::table('thresholds')->updateOrInsert(
                ['sensor_id' => $sensor->id],
                array_merge($data, [
                    'updated_at' => now(),
                    'created_at' => now(),
                ])
            );
        }
    }
}