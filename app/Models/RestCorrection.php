<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\CorrectionStatus;
use App\Models\AttendanceCorrection;
use App\Models\Rest;

class RestCorrection extends Model
{
    use HasFactory;

    protected $fillable = [
        'rest_id',
        'admin_id',
        'new_break_in',
        'new_break_out',
        'status',
    ];

    protected $casts = [
        'status' => CorrectionStatus::class,
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
