<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DeviceSeeder extends Seeder
{
    public function run(): void
    {
        $devices = [
            [
                'name' => 'Light',
                'type' => 'light',
                'status' => 'online',
                'is_on' => true,
                'mode' => 'manual',
            ],
            [
                'name' => 'Fan',
                'type' => 'fan',
                'status' => 'offline',
                'is_on' => false,
                'mode' => 'manual',
            ],
            [
                'name' => 'Pump',
                'type' => 'pump',
                'status' => 'online',
                'is_on' => true,
                'mode' => 'manual',
            ],
        ];

        foreach ($devices as $device) {
            DB::table('devices')->updateOrInsert(
                ['type' => $device['type']],
                array_merge($device, [
                    'updated_at' => now(),
                    'created_at' => now(),
                ])
            );
        }
    }
}