<?php

namespace App\Services;

use App\Models\Reading;
use App\Models\Sensor;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SensorService
{
    public function __construct(
        private ThresholdService $thresholdService,
        private AlertService $alertService
    ) {
    }

    /**
     * Simpan pembacaan, klasifikasikan, lalu proses alert.
     */
    public function storeReading(
        Sensor $sensor,
        float $value
    ): Reading {
        return DB::transaction(function () use ($sensor, $value) {
            $threshold = $sensor->threshold()->first();

            if (!$threshold) {
                throw new RuntimeException(
                    "Threshold untuk sensor {$sensor->name} belum tersedia."
                );
            }

            $classification = $this->thresholdService->classify(
                $value,
                $threshold
            );

            $reading = $sensor->readings()->create([
                'value' => $value,
                'condition' => $classification['condition'],
                'recorded_at' => now(),
            ]);

            $this->alertService->handle(
                $sensor,
                $reading,
                $classification
            );

            return $reading;
        });
    }
}