<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Sensor extends Model
{
    protected $fillable = [
        'name',
        'type',
        'unit',
        'location',
        'status',
    ];

    public function readings(): HasMany
    {
return $this->hasMany(Reading::class);
    }

    public function threshold(): HasOne
    {
        return $this->hasOne(Threshold::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }
}