<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehicleLocation extends Model
{
    protected $fillable = [
        'vehicle_id',
        'operator_id',
        'latitude',
        'longitude',
        'accuracy',
        'speed',
        'heading',
        'status',
        'is_sharing',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'accuracy' => 'float',
            'speed' => 'float',
            'heading' => 'integer',
            'is_sharing' => 'boolean',
            'recorded_at' => 'datetime',
        ];
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function operator()
    {
        return $this->belongsTo(Operator::class);
    }
}
