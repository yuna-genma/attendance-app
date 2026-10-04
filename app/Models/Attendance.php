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
use Illuminate\Support\Facades\DB;

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
                return null;

            return Carbon::parse($value)->locale('ja');
        });
    }

    protected function dateObject(): Attribute
    {
        return Attribute::get(function ($value, $attributes) {
            $rawValue = $this->getRawOriginal('date');
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

    public function updateWithCorrections(array $input): void
    {
        DB::transaction(function () use ($input) {
            $this->update([
                'clock_in' => $input['new_clock_in'],
                'clock_out' => $input['new_clock_out'],
            ]);

            $correction = $this->attendanceCorrections()->create([
                'user_id' => $this->user_id,
                'approved_by' => auth()->id(),
                'approval_status' => '承認済み',
                'new_clock_in' => $input['new_clock_in'],
                'new_clock_out' => $input['new_clock_out'],
                'comment' => $input['comment'] ?? ' ',
            ]);

            $this->rests()->delete();

            $breakIns = $input['new_break_in'] ?? [];
            $breakOuts = $input['new_break_out'] ?? [];

            foreach ($breakIns as $index => $breakIn) {
                if (is_null($breakIn) || $breakIn === '') {
                    continue;
                }
                $breakOut = ($breakOuts[$index] === ' ') ? null : ($breakOuts[$index] ?? null);

                $newRest = $this->rests()->create([
                    'break_in' => $breakIn,
                    'break_out' => $breakOut,
                ]);

                $correction->restCorrections()->create([
                    'rest_id' => $newRest->id,
                    'new_break_in' => $breakIn,
                    'new_break_out' => $breakOut,
                ]);
            }
        });
    }

    public function processAction(string $action, string $time): void
    {
        switch ($action) {
            case 'clock_out':
                $this->update([
                    'clock_out' => $time,
                    'attendance_status' => AttendanceStatus::LEFT,
                ]);
                break;

            case 'break_in':
                $this->rests()->create(['break_in' => $time]);
                $this->update(['attendance_status' => AttendanceStatus::BREAKING]);
                break;

            case 'break_out':
                $latestRest = $this->rests()->whereNull('break_out')->latest()->first();
                if ($latestRest) {
                    $latestRest->update(['break_out' => $time]);
                }
                $this->update(['attendance_status' => AttendanceStatus::WORKING]);
                break;
        }
    }
}
