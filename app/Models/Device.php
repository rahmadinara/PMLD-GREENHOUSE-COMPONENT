<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    protected $fillable = [
        'name',
        'type',
        'status',
        'is_on',
        'mode',
        'last_command_at',
    ];

    protected function casts(): array
    {
        return [
            'is_on' => 'boolean',
            'last_command_at' => 'datetime',
        ];
    }
}