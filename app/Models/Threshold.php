<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Threshold extends Model
{
    protected $fillable = [
        'sensor_id',
        'min_value',
        'warning_min',
        'warning_max',
        'max_value',
    ];

    protected function casts(): array
    {
        return [
            'min_value' => 'decimal:2',
            'warning_min' => 'decimal:2',
            'warning_max' => 'decimal:2',
            'max_value' => 'decimal:2',
        ];
    }

    public function sensor(): BelongsTo
    {
        return $this->belongsTo(Sensor::class);
    }
}