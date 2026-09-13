<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\CorrectionStatus;
use App\Models\Admin;
use App\Models\Attendance;
use App\Models\User;
use App\Models\RestCorrection;

class AttendanceCorrection extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'admin_id',
        'attendance_id',
        'new_clock_in',
        'new_clock_out',
        'comment',
        'status',
    ];

    protected $casts = [
        'status' => CorrectionStatus::class,
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    public function restCorrections()
    {
        return $this->hasMany(RestCorrection::class);
    }
}
