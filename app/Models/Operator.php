<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Operator extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'operator_code', 'first_name', 'middle_name', 'last_name', 'suffix', 'address', 'contact_number', 'email', 'valid_id_type', 'valid_id_number', 'status', 'remarks'];

    public function getFullNameAttribute(): string { return trim(implode(' ', array_filter([$this->first_name, $this->middle_name, $this->last_name, $this->suffix]))); }
    public function vehicles() { return $this->hasMany(Vehicle::class); }
    public function applications() { return $this->hasMany(Application::class); }
    public function franchises() { return $this->hasMany(Franchise::class); }
    public function permits() { return $this->hasMany(Permit::class); }
    public function renewals() { return $this->hasMany(Renewal::class); }
    public function violations() { return $this->hasMany(Violation::class); }
    public function user() { return $this->belongsTo(User::class); }
}
