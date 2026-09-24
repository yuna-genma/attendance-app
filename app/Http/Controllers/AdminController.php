<?php

namespace App\Http\Controllers;

use App\Http\Requests\MonthRequest;
use App\Http\Requests\UpdateAttendanceRequest;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Http\Requests\DateRequest;
use App\Models\User;

class AdminController extends Controller
{
    public function attendanceIndex(DateRequest $request)
    {
        $date = $request->input('validated_date');

        $previousDay = $date->copy()->subDay();
        $nextDay = $date->copy()->addDay();

        $targetDate = $date->format('Y-m-d');

        $generalUserIds = User::where('admin_status', false)->pluck('id');

        $attendanceRecords = Attendance::with('rests')
            ->whereDate('date', $targetDate)
            ->whereIn('user_id', $generalUserIds)
            ->get();

        $activeUserIds = $attendanceRecords->pluck('user_id')->unique();
        $users = User::whereIn('id', $activeUserIds)->get();


        return view('admin.admin-attendance-list', compact(
            'date',
            'previousDay',
            'nextDay',
            'users',
            'attendanceRecords'
        ));
    }

    public function show($id)
    {
        if (!auth()->user()->admin_status) {
            abort(403, 'この勤怠データを閲覧する権限がありません');
        }

        $attendance = Attendance::with('rests')->findOrFail($id);
        $user = $attendance->user;

        $adminId = auth()->id();
        if (!auth()->user()->admin_status) {
            abort(403, 'この勤怠データを閲覧する権限がありません');
        }

        $breaks = $attendance->rests->map(function ($rest) {
            return [
                'break_in' => $rest->break_in,
                'break_out' => $rest->break_out,
            ];
        })->toArray();

        $attendanceDate = $attendance->date_object;

        $attendanceRecord = [
            'id' => $attendance->id,
            'year' => $attendanceDate->format('Y年'),
            'date' => $attendanceDate->format('m月d日'),
            'clock_in' => $attendance->clock_in,
            'clock_out' => $attendance->clock_out,
            'breaks' => $breaks,
            'comment' => AttendanceCorrection::getLatestComment($attendance->id, $user->id),
        ];

        return view('admin.admin-detail', compact(
            'user',
            'attendanceRecord'
        ));
    }

    public function update(UpdateAttendanceRequest $request, $id)
    {
        if (!auth()->user()->admin_status) {
            abort(403, 'この操作を行う権限がありません');
        }

        $attendance = Attendance::findOrFail($id);

        $attendance->updateWithCorrections($request->validated());

        return redirect('/admin/attendance/list');
    }

    public function staffIndex()
    {
        $users = User::all();
        return view('admin.staff-list', compact('users'));
    }

    public function staffShow(MonthRequest $request, $id)
    {
        $user = User::findOrFail($id);

        $date = $request->input('validated_date');

        $previousMonth = $date->copy()->subMonth()->format('Y-m');
        $nextMonth = $date->copy()->addMonth()->format('Y-m');

        $attendanceRecords = Attendance::with('rests')
            ->where('user_id', $user->id)
            ->whereYear('date', $date->year)
            ->whereMonth('date', $date->month)
            ->oldest('date')
            ->get();

        $formattedAttendanceRecords = $attendanceRecords->map(function ($record) {
            return [
                'id' => $record->id,
                'date' => $record->date,
                'clock_in' => $record->clock_in ?: ' ',
                'clock_out' => $record->clock_out ?: ' ',
                'total_break_time' => $record->total_break_time,
                'total_time' => $record->total_time,
            ];
        })->toArray();


        return view('admin.staff-attendance-list', compact(
            'user',
            'formattedAttendanceRecords',
            'date',
            'previousMonth',
            'nextMonth'
        ));
    }
}
