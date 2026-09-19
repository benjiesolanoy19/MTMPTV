<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use HasFactory;
    protected $fillable = ['vehicle_code', 'operator_id', 'plate_number', 'engine_number', 'chassis_number', 'vehicle_type', 'make', 'model', 'color', 'year_model', 'registration_number', 'registration_expiry', 'status'];
    protected function casts(): array { return ['registration_expiry' => 'date']; }
    public function operator() { return $this->belongsTo(Operator::class); }
    public function applications() { return $this->hasMany(Application::class); }
    public function franchises() { return $this->hasMany(Franchise::class); }
    public function permits() { return $this->hasMany(Permit::class); }
    public function violations() { return $this->hasMany(Violation::class); }
    public function locations() { return $this->hasMany(VehicleLocation::class); }
    public function latestLocation() { return $this->hasOne(VehicleLocation::class)->latestOfMany('recorded_at'); }
}
