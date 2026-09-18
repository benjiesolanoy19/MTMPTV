<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    use HasFactory;
    protected $fillable = ['application_number', 'operator_id', 'vehicle_id', 'application_type', 'date_submitted', 'status', 'reviewed_by', 'reviewed_at', 'rejection_reason', 'remarks'];
    protected function casts(): array { return ['date_submitted' => 'date', 'reviewed_at' => 'datetime']; }
    public function operator() { return $this->belongsTo(Operator::class); }
    public function vehicle() { return $this->belongsTo(Vehicle::class); }
    public function reviewer() { return $this->belongsTo(User::class, 'reviewed_by'); }
}
