<?php

namespace App\Services;

use App\Models\Threshold;
use Illuminate\Validation\ValidationException;

class ThresholdService
{

    public function classify(float $value, Threshold $threshold): array
    {
        $min = $threshold->min_value !== null
            ? (float) $threshold->min_value
            : null;

        $warningMin = $threshold->warning_min !== null
            ? (float) $threshold->warning_min
            : null;

        $warningMax = $threshold->warning_max !== null
            ? (float) $threshold->warning_max
            : null;

        $max = $threshold->max_value !== null
            ? (float) $threshold->max_value
            : null;

        // Critical low
        if ($min !== null && $value < $min) {
            return $this->result(
                'critical',
                'low',
                $min,
                'Nilai berada di bawah batas minimum.'
            );
        }

        // Critical high
        if ($max !== null && $value > $max) {
            return $this->result(
                'critical',
                'high',
                $max,
                'Nilai berada di atas batas maksimum.'
            );
        }

        // Warning low
        if ($warningMin !== null && $value < $warningMin) {
            return $this->result(
                'warning',
                'low',
                $warningMin,
                'Nilai berada di bawah rentang normal.'
            );
        }

        // Warning high
        if ($warningMax !== null && $value > $warningMax) {
            return $this->result(
                'warning',
                'high',
                $warningMax,
                'Nilai berada di atas rentang normal.'
            );
        }

        return [
            'condition' => 'normal',
            'direction' => null,
            'severity' => null,
            'threshold_value' => null,
            'message' => 'Nilai sensor berada dalam rentang normal.',
        ];
    }

    public function validateConfiguration(array $data): void
    {
        if (
            !isset($data['min_value'])
            || $data['min_value'] === ''
            || !is_numeric($data['min_value'])
        ) {
            throw ValidationException::withMessages([
                'min_value' => 'Batas minimum wajib berupa angka.',
            ]);
        }

        $fields = [
            'min_value',
            'warning_min',
            'warning_max',
            'max_value',
        ];

        $previousValue = null;

        foreach ($fields as $field) {
            $value = $data[$field] ?? null;

            if ($value === null || $value === '') {
                continue;
            }

            if (!is_numeric($value)) {
                throw ValidationException::withMessages([
                    $field => "Kolom {$field} harus berupa angka.",
                ]);
            }

            $numericValue = (float) $value;

            if (
                $previousValue !== null
                && $numericValue <= $previousValue
            ) {
                throw ValidationException::withMessages([
                    $field => "Nilai {$field} harus lebih besar dari batas sebelumnya.",
                ]);
            }

            $previousValue = $numericValue;
        }
    }

    private function result(
        string $condition,
        string $direction,
        float $thresholdValue,
        string $message
    ): array {
        return [
            'condition' => $condition,
            'direction' => $direction,
            'severity' => $condition,
            'threshold_value' => $thresholdValue,
            'message' => $message,
        ];
    }
}