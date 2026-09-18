<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Franchise extends Model
{
    protected $fillable = ['franchise_number', 'operator_id', 'vehicle_id', 'application_id', 'issue_date', 'expiry_date', 'status', 'remarks'];
    protected function casts(): array { return ['issue_date' => 'date', 'expiry_date' => 'date']; }
    public function operator() { return $this->belongsTo(Operator::class); }
    public function vehicle() { return $this->belongsTo(Vehicle::class); }
    public function application() { return $this->belongsTo(Application::class); }
    public function getComputedStatusAttribute(): string { return $this->expiry_date->isPast() ? 'Expired' : ($this->expiry_date->lte(Carbon::today()->addDays(30)) ? 'Expiring Soon' : 'Active'); }
}
