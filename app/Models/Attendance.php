<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\AttendanceStatus;

class Attendance extends Model
{
    use HasFactory;

    protected $casts = [
        'attendance_status' => AttendanceStatus::class,
    ];
}
