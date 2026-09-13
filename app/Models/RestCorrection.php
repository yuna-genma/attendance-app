<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\AttendanceCorrection;
use App\Models\Rest;

class RestCorrection extends Model
{
    use HasFactory;

    protected $fillable = [
        'rest_id',
        'attendance_correction_id',
        'new_break_in',
        'new_break_out',
    ];

    public function attendanceCorrection()
    {
        return $this->belongsTo(AttendanceCorrection::class);
    }

    public function originalRest()
    {
        return $this->belongsTo(Rest::class);
    }

}
