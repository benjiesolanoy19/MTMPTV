<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    protected $fillable = ['report_number', 'submitted_by', 'report_type', 'date_submitted', 'location', 'description', 'status', 'processing_status', 'resolution'];
    protected function casts(): array { return ['date_submitted' => 'date']; }
    public function submitter() { return $this->belongsTo(User::class, 'submitted_by'); }
}