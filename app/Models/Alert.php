<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alert extends Model
{
    protected $fillable = [
        'sensor_id',
        'reading_id',
        'direction',
        'message',
        'value',
        'threshold_value',
        'severity',
        'status',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'threshold_value' => 'decimal:2',
            'resolved_at' => 'datetime',
        ];
    }

    public function sensor(): BelongsTo
    {
        return $this->belongsTo(Sensor::class);
    }

    public function reading(): BelongsTo
    {
        return $this->belongsTo(Reading::class);
    }
}