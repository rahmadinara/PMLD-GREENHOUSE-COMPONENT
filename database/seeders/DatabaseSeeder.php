<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SensorSeeder::class,
            ThresholdSeeder::class,
            DeviceSeeder::class,
            HistoricalReadingSeeder::class,
            UserSeeder::class,
        ]);
    }
}