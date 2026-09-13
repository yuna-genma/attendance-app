<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\AttendanceStatus;
use App\Models\User;
use App\Models\AttendanceCorrection;
use App\Models\Rest;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'attendance_status',
        'date',
        'clock_in',
        'clock_out',
    ];

    protected $casts = [
        'attendance_status' => AttendanceStatus::class,
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function rests()
    {
        return $this->hasMany(Rest::class);
    }

    public function attendanceCorrections()
    {
        return $this->hasMany(AttendanceCorrection::class);
    }

    protected function date(): Attribute
    {
        return Attribute::get(function ($value) {
            if (!$value)
                return '';

            return Carbon::parse($value)->locale('ja')->isoFormat('MM/DD(ddd)');
        });
    }

    protected function dateObject(): Attribute
    {
        return Attribute::get(function ($value, $attributes) {
            $rawValue = $attributes['date'] ?? null;
            if (!$rawValue)
                return null;
            return Carbon::parse($rawValue)->locale('ja');
        });
    }
    protected function clockIn(): Attribute
    {
        return Attribute::get(fn($value) => $value ? Carbon::parse($value)->format('H:i') : '');
    }

    protected function clockOut(): Attribute
    {
        return Attribute::get(fn($value) => $value ? Carbon::parse($value)->format('H:i') : '');
    }

    protected function totalBreakTime(): Attribute
    {
        return Attribute::make(
            get: function () {
                $totalMinutes = 0;
                $rests = $this->rests()->get();
                foreach ($rests as $rest) {
                    if ($rest->break_in && $rest->break_out) {
                        $in = Carbon::parse($rest->break_in);
                        $out = Carbon::parse($rest->break_out);
                        $totalMinutes += $out->diffInMinutes($in);
                    }
                }
                $hours = floor($totalMinutes / 60);
                $remainingMinutes = $totalMinutes % 60;
                return sprintf('%d:%02d', $hours, $remainingMinutes);
            }
        );
    }

    protected function totalTime(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->clock_in || !$this->clock_out) {
                    return '0:00';
                }

                $clockIn = Carbon::parse($this->clock_in);
                $clockOut = Carbon::parse($this->clock_out);

                $totalStayMinutes = $clockIn->diffInMinutes($clockOut);

                $totalBreakMinutes = 0;
                foreach ($this->rests as $rest) {
                    if ($rest->break_in && $rest->break_out) {
                        $totalBreakMinutes += Carbon::parse($rest->break_in)->diffInMinutes(Carbon::parse($rest->break_out));
                    }
                }

                $workingMinutes = $totalStayMinutes - $totalBreakMinutes;
                $workingMinutes = $workingMinutes > 0 ? $workingMinutes : 0;

                $hours = floor($workingMinutes / 60);
                $remainingMinutes = $workingMinutes % 60;
                return sprintf('%d:%02d', $hours, $remainingMinutes);
            }
        );
    }
}
