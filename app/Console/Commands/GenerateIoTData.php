<?php

namespace App\Console\Commands;

use App\Models\Sensor;
use App\Services\SensorService;
use Illuminate\Console\Command;
use InvalidArgumentException;

class GenerateIoTData extends Command
{
    protected $signature = 'iot:generate-data
        {--count=0 : Number of rounds, 0 means run continuously}
        {--interval=5 : Delay between rounds in seconds}
        {--scenario=auto : Sensor data scenario}';

    protected $description =
        'Generate simulated IoT sensor data for the greenhouse';

    public function handle(SensorService $sensorService): int
    {
        $allowedScenarios = [
            'auto',
            'normal',
            'warning',
            'critical',
            'hot-day',
            'dry-soil',
            'low-humidity',
            'low-light',
        ];

        $requestedScenario = strtolower(
            (string) $this->option('scenario')
        );

        if (!in_array($requestedScenario, $allowedScenarios, true)) {
            $this->error(
                'Invalid scenario: ' . $requestedScenario
            );

            $this->line(
                'Available scenarios: ' . implode(', ', $allowedScenarios)
            );

            return self::FAILURE;
        }

        $count = max(0, (int) $this->option('count'));
        $interval = max(0, (int) $this->option('interval'));

        // Prevent a continuous loop from running without a delay.
        if ($count === 0 && $interval === 0) {
            $interval = 1;
        }

        $sensors = Sensor::where('status', 'active')->get();

        if ($sensors->isEmpty()) {
            $this->error('No active sensors found in the database.');

            return self::FAILURE;
        }

        $this->info('IoT Sensor Simulator started.');
        $this->line('Number of active sensors: ' . $sensors->count());

        $this->line(
            $count === 0
                ? 'Mode: running continuously (press Ctrl+C to stop)'
                : "Number of rounds: {$count}"
        );

        $round = 0;

        while ($count === 0 || $round < $count) {
            $round++;

            $scenario = $requestedScenario === 'auto'
                ? $this->chooseScenario()
                : $requestedScenario;

            $this->newLine();

            $this->info(
                "Round {$round} | Scenario: {$scenario}"
            );

            foreach ($sensors as $sensor) {
                $value = $this->generateValue(
                    $sensor->type,
                    $scenario
                );

                $reading = $sensorService->storeReading(
                    $sensor,
                    $value
                );

                $this->line(sprintf(
                    '%s: %.2f %s => %s',
                    $sensor->name,
                    $value,
                    $sensor->unit,
                    strtoupper($reading->condition)
                ));
            }

            if ($count > 0 && $round >= $count) {
                break;
            }

            if ($interval > 0) {
                sleep($interval);
            }
        }

        $this->info('Simulator finished.');

        return self::SUCCESS;
    }

    /**
     * Select a scenario automatically using weighted probabilities.
     */
    private function chooseScenario(): string
    {
        $roll = random_int(1, 100);

        return match (true) {
            $roll <= 50 => 'normal',
            $roll <= 70 => 'warning',
            $roll <= 80 => 'critical',
            $roll <= 87 => 'hot-day',
            $roll <= 92 => 'dry-soil',
            $roll <= 97 => 'low-humidity',
            default => 'low-light',
        };
    }

    /**
     * Generate a sensor value based on the selected scenario.
     */
    private function generateValue(
        string $type,
        string $scenario
    ): float {
        if ($scenario === 'normal') {
            return $this->normalValue($type);
        }

        if ($scenario === 'warning') {
            return match ($type) {
                'temperature' => random_int(2550, 2690) / 100,
                'humidity' => random_int(5200, 5900) / 100,
                'soil_moisture' => random_int(3200, 3900) / 100,
                'brightness' => (float) random_int(2100, 2450),
                'water_level' => random_int(5500, 8000) / 100,
                default => $this->unsupportedSensor($type),
            };
        }

        if ($scenario === 'critical') {
            return match ($type) {
                'temperature' => random_int(3600, 3800) / 100,
                'humidity' => random_int(8100, 9000) / 100,
                'soil_moisture' => random_int(1000, 2500) / 100,
                'brightness' => (float) random_int(1000, 1900),
                'water_level' => random_int(3000, 4900) / 100,
                default => $this->unsupportedSensor($type),
            };
        }

        if ($scenario === 'hot-day') {
            return match ($type) {
                'temperature' => random_int(3350, 3500) / 100,
                'brightness' => (float) random_int(12000, 22000),
                default => $this->normalValue($type),
            };
        }

        if ($scenario === 'dry-soil' && $type === 'soil_moisture') {
            return random_int(1000, 2500) / 100;
        }

        if ($scenario === 'low-humidity' && $type === 'humidity') {
            return random_int(5200, 5900) / 100;
        }

        if ($scenario === 'low-light' && $type === 'brightness') {
            return (float) random_int(2100, 2450);
        }

        // All other sensors use normal values.
        return $this->normalValue($type);
    }

    /**
     * Generate normal values based on the project's threshold ranges.
     */
    private function normalValue(string $type): float
    {
        return match ($type) {
            'temperature' => random_int(2800, 3200) / 100,
            'humidity' => random_int(6200, 6800) / 100,
            'soil_moisture' => random_int(4200, 4800) / 100,
            'brightness' => (float) random_int(3000, 12000),
            'water_level' => random_int(6000, 9000) / 100,
            default => $this->unsupportedSensor($type),
        };
    }

    private function unsupportedSensor(string $type): never
    {
        throw new InvalidArgumentException(
            "Sensor type '{$type}' is not supported by the simulator."
        );
    }
}
