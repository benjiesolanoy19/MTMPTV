<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffApplication extends Model
{
    protected $fillable = [
        'user_id',
        'full_name',
        'contact_number',
        'address',
        'date_of_birth',
        'preferred_position',
        'department',
        'skills',
        'experience',
        'reason',
        'additional_information',
        'status',
        'admin_remarks',
        'submitted_at',
        'reviewed_at',
        'reviewed_by',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function applicant()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
