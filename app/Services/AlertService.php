<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\Reading;
use App\Models\Sensor;

class AlertService
{

    public function handle(
        Sensor $sensor,
        Reading $reading,
        array $classification
    ): void {
        // Jika kondisi normal, selesaikan alert aktif sensor ini.
        if ($classification['condition'] === 'normal') {
            Alert::where('sensor_id', $sensor->id)
                ->whereIn('status', ['open', 'acknowledged'])
                ->update([
                    'status' => 'resolved',
                    'resolved_at' => now(),
                    'updated_at' => now(),
                ]);

            return;
        }

        $direction = $classification['direction'];
        $severity = $classification['severity'];

        // Cegah alert sejenis berulang dalam cooldown 5 menit.
        $duplicateExists = Alert::where('sensor_id', $sensor->id)
            ->where('direction', $direction)
            ->where('severity', $severity)
            ->where('status', 'open')
            ->where('created_at', '>=', now()->subMinutes(5))
            ->exists();

        if ($duplicateExists) {
            return;
        }

        $thresholdValue = $classification['threshold_value'];

        $message = sprintf(
            '%s: nilai %.2f %s. %s',
            $sensor->name,
            (float) $reading->value,
            $sensor->unit,
            $classification['message']
        );

        Alert::create([
            'sensor_id' => $sensor->id,
            'reading_id' => $reading->id,
            'direction' => $direction,
            'message' => $message,
            'value' => $reading->value,
            'threshold_value' => $thresholdValue,
            'severity' => $severity,
            'status' => 'open',
        ]);
    }
}