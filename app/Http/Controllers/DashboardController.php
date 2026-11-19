<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\Reading;
use App\Models\Sensor;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Validate request parameters
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'sensor_id' => 'nullable|integer|exists:sensors,id',
        ]);

        // Set the date range
        $startDate = isset($validated['start_date'])
            ? Carbon::parse($validated['start_date'])->startOfDay()
            : (isset($validated['end_date'])
                ? Carbon::parse($validated['end_date'])->subDays(6)->startOfDay()
                : now()->subDays(6)->startOfDay());

        $endDate = isset($validated['end_date'])
            ? Carbon::parse($validated['end_date'])->endOfDay()
            : now()->endOfDay();

        if ($startDate->greaterThan($endDate)) {
            return response()->json([
                'message' => 'The start date must not be after the end date.',
            ], 422);
        }

        // Get readings within the selected date range
        $readingsQuery = Reading::query()
            ->whereBetween('recorded_at', [$startDate, $endDate]);

        // Apply sensor filter if provided
        if (isset($validated['sensor_id'])) {
            $readingsQuery->where('sensor_id', $validated['sensor_id']);
        }

        // Get alerts within the selected date range
        $alertsQuery = Alert::query()
            ->whereBetween('created_at', [$startDate, $endDate]);

        if (isset($validated['sensor_id'])) {
            $alertsQuery->where('sensor_id', $validated['sensor_id']);
        }

        // Calculate dashboard summary
        $summary = [
            'total_sensors' => Sensor::count(),

            'active_sensors' => Sensor::where('status', 'active')->count(),

            'total_readings' => (clone $readingsQuery)->count(),

            'total_alerts' => (clone $alertsQuery)->count(),

            'open_alerts' => (clone $alertsQuery)
                ->where('status', 'open')
                ->count(),

            'critical_alerts' => (clone $alertsQuery)
                ->where('severity', 'critical')
                ->where('status', '!=', 'resolved')
                ->count(),
        ];

        // Get sensor statistics
        $sensors = Sensor::query()
            ->orderBy('name')
            ->get()
            ->map(function ($sensor) use ($startDate, $endDate) {
                $sensorReadings = $sensor->readings()
                    ->whereBetween('recorded_at', [$startDate, $endDate]);

                $statistics = (clone $sensorReadings)
                    ->selectRaw('
                        COUNT(*) as total,
                        MIN(value) as min_value,
                        MAX(value) as max_value,
                        AVG(value) as average_value
                    ')
                    ->first();

                $latestReading = (clone $sensorReadings)
                    ->latest('recorded_at')
                    ->first();

                return [
                    'id' => $sensor->id,
                    'name' => $sensor->name,
                    'type' => $sensor->type,
                    'unit' => $sensor->unit,
                    'location' => $sensor->location,
                    'status' => $sensor->status,

                    'statistics' => [
                        'total' => $statistics->total,
                        'minimum' => $statistics->min_value,
                        'maximum' => $statistics->max_value,
                        'average' => $statistics->average_value,
                    ],

                    'latest_reading' => $latestReading,
                ];
            });

        // Get the latest readings
        $recentReadings = (clone $readingsQuery)
            ->with('sensor:id,name,type,unit')
            ->orderByDesc('recorded_at')
            ->limit(10)
            ->get();

        // Get the latest alerts
        $recentAlerts = (clone $alertsQuery)
            ->with('sensor:id,name,type,unit')
            ->latest()
            ->limit(10)
            ->get();

        // Return dashboard data as JSON
        return response()->json([
            'filters' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'sensor_id' => $validated['sensor_id'] ?? null,
            ],

            'summary' => $summary,

            'sensors' => $sensors,

            'recent_readings' => $recentReadings,

            'recent_alerts' => $recentAlerts,
        ]);
    }
}
