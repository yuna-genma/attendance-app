<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\CorrectionStatus;
use App\Models\Attendance;
use App\Models\User;
use App\Models\RestCorrection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceCorrection extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'approved_by',
        'attendance_id',
        'new_clock_in',
        'new_clock_out',
        'comment',
        'approval_status',
    ];

    protected $casts = [
        'approval_status' => CorrectionStatus::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function restCorrections()
    {
        return $this->hasMany(RestCorrection::class);
    }

    public static function getLatestComment(int $attendanceId, int $userId): string
    {
        return self::where('attendance_id', $attendanceId)
            ->where('user_id', $userId)
            ->latest()
            ->value('comment') ?? ' ';
    }
}
