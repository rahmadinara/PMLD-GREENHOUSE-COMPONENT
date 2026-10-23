
<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\Reading;
use App\Models\Sensor;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ReportService
{
    /**
     * Membuat data laporan berdasarkan filter.
     *
     * Filter yang didukung:
     * - start_date: YYYY-MM-DD
     * - end_date: YYYY-MM-DD
     * - sensor_id: opsional
     */
    public function generate(array $filters = []): array
    {
        $validator = Validator::make($filters, [
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d'],
            'sensor_id' => ['nullable', 'integer', 'exists:sensors,id'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $filters = $validator->validated();

        $startDate = isset($filters['start_date'])
            ? Carbon::parse($filters['start_date'])->startOfDay()
            : now()->subDays(6)->startOfDay();

        $endDate = isset($filters['end_date'])
            ? Carbon::parse($filters['end_date'])->endOfDay()
            : now()->endOfDay();

        if ($startDate->gt($endDate)) {
            throw ValidationException::withMessages([
                'start_date' => 'Tanggal awal tidak boleh melebihi tanggal akhir.',
            ]);
        }

        $sensorId = isset($filters['sensor_id'])
            ? (int) $filters['sensor_id']
            : null;

        /*
         * Query dasar pembacaan.
         */
        $readingQuery = Reading::query()
            ->whereBetween('recorded_at', [$startDate, $endDate])
            ->when(
                $sensorId !== null,
                fn ($query) => $query->where('sensor_id', $sensorId)
            );

        /*
         * Statistik keseluruhan.
         */
        $overallStats = (clone $readingQuery)
            ->selectRaw('
                COUNT(*) as total_readings,
                MIN(value) as min_value,
                MAX(value) as max_value,
                AVG(value) as avg_value
            ')
            ->first();

        /*
         * Statistik masing-masing sensor.
         * Sensor tanpa reading tetap dimasukkan ke laporan.
         */
        $sensors = Sensor::query()
            ->when(
                $sensorId !== null,
                fn ($query) => $query->whereKey($sensorId)
            )
            ->orderBy('name')
            ->get();

        $sensorStats = $sensors->map(function ($sensor) use (
            $startDate,
            $endDate
        ) {
            $stats = Reading::query()
                ->where('sensor_id', $sensor->id)
                ->whereBetween(
                    'recorded_at',
                    [$startDate, $endDate]
                )
                ->selectRaw('
                    COUNT(*) as total_readings,
                    MIN(value) as min_value,
                    MAX(value) as max_value,
                    AVG(value) as avg_value
                ')
                ->first();

            return [
                'sensor_id' => $sensor->id,
                'name' => $sensor->name,
                'type' => $sensor->type,
                'unit' => $sensor->unit,
                'total_readings' => (int) $stats->total_readings,
                'min_value' => $stats->min_value,
                'max_value' => $stats->max_value,
                'avg_value' => $stats->avg_value !== null
                    ? round((float) $stats->avg_value, 2)
                    : null,
            ];
        });

        /*
         * Rekap alert berdasarkan severity dan status.
         * Periode menggunakan waktu pembuatan alert.
         */
        $alertBreakdown = Alert::query()
            ->whereBetween(
                'created_at',
                [$startDate, $endDate]
            )
            ->when(
                $sensorId !== null,
                fn ($query) => $query->where('sensor_id', $sensorId)
            )
            ->selectRaw('severity, status, COUNT(*) as total')
            ->groupBy('severity', 'status')
            ->orderBy('severity')
            ->orderBy('status')
            ->get();

        /*
         * Daftar pembacaan terbaru.
         * Dibatasi 100 baris agar respons tidak terlalu besar.
         */
        $recentReadings = (clone $readingQuery)
            ->with('sensor:id,name,type,unit')
            ->orderByDesc('recorded_at')
            ->limit(100)
            ->get([
                'id',
                'sensor_id',
                'value',
                'condition',
                'recorded_at',
            ]);

        return [
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],

            'filter' => [
                'sensor_id' => $sensorId,
            ],

            'summary' => [
                'total_readings' => (int) $overallStats->total_readings,
                'min_value' => $overallStats->min_value,
                'max_value' => $overallStats->max_value,
                'avg_value' => $overallStats->avg_value !== null
                    ? round((float) $overallStats->avg_value, 2)
                    : null,
            ],

            'sensors' => $sensorStats->values()->all(),

            'alerts' => [
                'total' => $alertBreakdown->sum('total'),
                'breakdown' => $alertBreakdown,
            ],

            'recent_readings' => $recentReadings,
        ];
    }
}
