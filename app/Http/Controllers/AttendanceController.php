<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceActionRequest;
use App\Models\Attendance;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
class AttendanceController extends Controller
{

    public function create()
    {
        $user = auth()->user();
        $today = Carbon::now('Asia/Tokyo')->toDateString();

        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        $status = $attendance ? $attendance->attendance_status->value : '勤務外';

        $formattedDate = $today;
        $formattedTime = Carbon::now('Asia/Tokyo')->format('H:i');
        return view('user.attendance-register', compact(
            'user',
            'formattedDate',
            'formattedTime',
            'status'
        ));
    }

    public function store(AttendanceActionRequest $request)
    {
        $user = auth()->user();
        $action = $request->input('action');
        $today = Carbon::today()->toDateString();
        $nowTime = Carbon::now('Asia/Tokyo')->format('H:i');

        if ($action === 'clock_in') {
            Attendance::create([
                'user_id' => $user->id,
                'date' => $today,
                'clock_in' => $nowTime,
                'attendance_status' => '出勤中',
            ]);
            return redirect()->back();
        }

        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->firstOrFail();

        if ($action === 'clock_out') {
            $attendance->update([
                'clock_out' => $nowTime,
                'attendance_status' => '退勤済'
            ]);
        }

        if ($action === 'break_in') {
            $attendance->rests()->create([
                'break_in' => $nowTime,
            ]);
            $attendance->update(['attendance_status' => '休憩中']);
        }

        if ($action === 'break_out') {
            $latestRest = $attendance->rests()->whereNull('break_out')->latest()->first();
            if ($latestRest) {
                $latestRest->update(['break_out' => $nowTime]);
            }
            $attendance->update(['attendance_status' => '出勤中']);
        }

        return redirect()->back();
    }

    public function userAttendanceIndex(Request $request)
    {
        $user = auth()->user();

        $dateString = $request->query('date');

        if ($dateString) {
            $date = Carbon::parse($dateString)->startOfMonth();
        } else {
            $date = Carbon::today()->startOfMonth();
        }

        $previousMonth = $date->copy()->subMonth()->format('Y-m');
        $nextMonth = $date->copy()->addMonth()->format('Y-m');

        $formattedAttendanceRecords = Attendance::with('rests')
            ->where('user_id', $user->id)
            ->whereYear('date', $date)
            ->whereMonth('date', $date)
            ->oldest()
            ->get();

        return view('user.user-attendance-list', compact(
            'formattedAttendanceRecords',
            'date',
            'previousMonth',
            'nextMonth'
        ));
    }
}