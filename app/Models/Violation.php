<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Violation extends Model
{
    protected $fillable = ['violation_number', 'operator_id', 'vehicle_id', 'violation_type', 'violation_date', 'location', 'description', 'penalty_amount', 'payment_status', 'payment_date', 'status', 'recorded_by', 'remarks'];
    protected function casts(): array { return ['violation_date' => 'date', 'payment_date' => 'date', 'penalty_amount' => 'decimal:2']; }
    public function operator() { return $this->belongsTo(Operator::class); }
    public function vehicle() { return $this->belongsTo(Vehicle::class); }
    public function recorder() { return $this->belongsTo(User::class, 'recorded_by'); }
}
