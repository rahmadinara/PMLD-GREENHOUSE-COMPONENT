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
                'min' => 20,
                'max' => 35,
                'margin' => 10,
            ],
            'humidity' => [
                'min' => 40,
                'max' => 90,
                'margin' => 10,
            ],
            'soil_moisture' => [
                'min' => 20,
                'max' => 60,
                'margin' => 10,
            ],
            'brightness' => [
                'min' => 50,
                'max' => 1000,
                'margin' => 10,
            ],
            'water_level' => [
                'min' => 20,
                'max' => 100,
                'margin' => 10,
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
                [
                    'min_value' => $data['min'],
                    'max_value' => $data['max'],
                    'warning_margin' => $data['margin'],
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}