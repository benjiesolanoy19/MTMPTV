<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdministratorApplication extends Model
{
    protected $fillable = [
        'user_id',
        'reason',
        'experience',
        'additional_information',
        'status',
        'submitted_at',
        'reviewed_at',
        'reviewed_by',
        'admin_remarks',
    ];

    protected function casts(): array
    {
        return [
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
