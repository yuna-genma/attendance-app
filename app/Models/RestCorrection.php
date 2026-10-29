<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RestCorrection extends Model
{
    use HasFactory;

    protected $fillable = [
        'rest_id',
        'attendance_correction_id',
        'new_break_in',
        'new_break_out',
    ];

    public function attendanceCorrection(): BelongsTo
    {
        return $this->belongsTo(AttendanceCorrection::class);
    }

    public function originalRest(): BelongsTo
    {
        return $this->belongsTo(Rest::class);
    }
}
