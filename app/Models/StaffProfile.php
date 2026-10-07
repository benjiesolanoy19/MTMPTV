<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffProfile extends Model
{
    protected $fillable = [
        'user_id',
        'staff_id',
        'position',
        'department',
        'contact_number',
        'address',
        'date_of_birth',
        'skills',
        'experience',
        'emergency_contact',
        'additional_information',
        'profile_completed',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'profile_completed' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
