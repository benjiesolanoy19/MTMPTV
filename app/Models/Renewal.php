<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Renewal extends Model
{
    protected $fillable = ['renewal_number', 'operator_id', 'vehicle_id', 'previous_permit_id', 'previous_franchise_id', 'application_id', 'date_applied', 'new_expiry_date', 'status', 'processed_by', 'processed_at', 'remarks'];
    protected function casts(): array { return ['date_applied' => 'date', 'new_expiry_date' => 'date', 'processed_at' => 'datetime']; }
    public function operator() { return $this->belongsTo(Operator::class); }
    public function vehicle() { return $this->belongsTo(Vehicle::class); }
}