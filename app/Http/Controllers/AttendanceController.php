<?php

namespace App\Http\Controllers;

use App\Enums\AttendanceStatus;
use App\Http\Requests\AttendanceActionRequest;
use App\Http\Requests\UserAttendanceIndexRequest;
use App\Models\Attendance;
use Illuminate\Support\Carbon;
class AttendanceController extends Controller
{

    public function create()
    {
        $user = auth()->user();
        $now = Carbon::now('Asia/Tokyo');
        $today = $now->toDateString();

        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        $user->attendance_status = $attendance
            ? $attendance->attendance_status->value
            : AttendanceStatus::BEFORE_WORK->value;

        $formattedDate = $today;
        $formattedTime = $now->format('H:i');

        return view('user.attendance-register', compact(
            'user',
            'formattedDate',
            'formattedTime',
        ));
    }

    public function store(AttendanceActionRequest $request)
    {
        $user = auth()->user();
        $action = $request->input('action');

        $now = Carbon::now('Asia/Tokyo');
        $today = $now->toDateString();
        $nowTime = $now->format('H:i');

        if ($action === 'clock_in') {
            Attendance::create([
                'user_id' => $user->id,
                'date' => $today,
                'clock_in' => $nowTime,
                'attendance_status' => AttendanceStatus::WORKING,
            ]);
            return redirect()->back();
        }

        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->firstOrFail();

        $attendance->prrcessAction($action, $nowTime);

        return redirect()->back();
    }

    public function userAttendanceIndex(UserAttendanceIndexRequest $request)
    {
        $validated = $request->validated();

        $user = auth()->user();
        $date = isset($validated['date'])
            ? Carbon::parse($validated['date'])->startOfMonth()
            : Carbon::today()->startOfMonth();

        $previousMonth = $date->copy()->subMonth()->format('Y-m');
        $nextMonth = $date->copy()->addMonth()->format('Y-m');

        $formattedAttendanceRecords = Attendance::with('rests')
            ->where('user_id', $user->id)
            ->whereYear('date', $date->year)
            ->whereMonth('date', $date->month)
            ->oldest('date')
            ->get();

        return view('user.user-attendance-list', compact(
            'formattedAttendanceRecords',
            'date',
            'previousMonth',
            'nextMonth'
        ));
    }
}