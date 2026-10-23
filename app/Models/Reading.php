<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reading extends Model
{
    protected $fillable = [
        'sensor_id',
        'value',
        'condition',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'recorded_at' => 'datetime',
        ];
    }

    public function sensor(): BelongsTo
    {
        return $this->belongsTo(Sensor::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }
}