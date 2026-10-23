<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SensorSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $sensors = [
            [
                'name' => 'Temperature',
                'type' => 'temperature',
                'unit' => '°C',
            ],
            [
                'name' => 'Humidity',
                'type' => 'humidity',
                'unit' => '%',
            ],
            [
                'name' => 'Soil Moisture',
                'type' => 'soil_moisture',
                'unit' => '%',
            ],
            [
                'name' => 'Brightness',
                'type' => 'brightness',
                'unit' => 'Lux',
            ],
            [
                'name' => 'Water Level',
                'type' => 'water_level',
                'unit' => '%',
            ],
        ];

        foreach ($sensors as $sensor) {
            DB::table('sensors')->updateOrInsert(
                ['type' => $sensor['type']],
                [
                    'name' => $sensor['name'],
                    'unit' => $sensor['unit'],
                    'location' => 'Greenhouse A',
                    'status' => 'active',
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }
    }
}