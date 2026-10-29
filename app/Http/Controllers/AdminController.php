<?php

namespace App\Http\Controllers;

use App\Http\Requests\MonthRequest;
use App\Http\Requests\UpdateAttendanceRequest;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Http\Requests\DateRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Carbon\Carbon;

class AdminController extends Controller
{
    public function attendanceIndex(DateRequest $request): View
    {
        if (!auth()->user()->admin_status) {
            abort(403, 'このデータを閲覧する権限がありません');
        }

        $date = $request->input('validated_date');

        $previousDay = $date->copy()->startOfDay()->subDay();
        $nextDay = $date->copy()->startOfDay()->addDay();

        $targetDate = $date->format('Y-m-d');

        $attendanceRecords = Attendance::with('rests')
            ->whereDate('date', $targetDate)
            ->get();

        $users = User::all();


        return view('admin.admin-attendance-list', compact(
            'date',
            'previousDay',
            'nextDay',
            'users',
            'attendanceRecords'
        ));
    }

    public function show($id): View
    {
        if (!auth()->user()->admin_status) {
            abort(403, 'この勤怠データを閲覧する権限がありません');
        }

        $attendance = Attendance::with('rests')->findOrFail($id);
        $user = $attendance->user;

        $adminId = auth()->id();

        $breaks = $attendance->rests->map(function ($rest) {
            return [
                'break_in' => $rest->break_in,
                'break_out' => $rest->break_out,
            ];
        })->toArray();

        $attendanceDate = $attendance->date;

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

    public function update(UpdateAttendanceRequest $request, $id): RedirectResponse
    {
        if (!auth()->user()->admin_status) {
            abort(403, 'この操作を行う権限がありません');
        }

        $attendance = Attendance::findOrFail($id);

        $attendance->updateWithCorrections($request->validated());

        return redirect('/admin/attendance/list');
    }

    public function staffIndex(): View
    {
        if (!auth()->user()->admin_status) {
            abort(403, 'このデータを閲覧する権限がありません');
        }

        $users = User::where('admin_status', false)->get();
        return view('admin.staff-list', compact('users'));
    }

    public function staffShow(MonthRequest $request, $id): View
    {
        if (!auth()->user()->admin_status) {
            abort(403, 'このデータを閲覧する権限がありません');
        }

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
                'date' => $record->date->isoFormat('MM/DD(ddd)'),
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

    public function exportCsv(Request $request): StreamedResponse
    {
        if (!auth()->user()->admin_status) {
            abort(403, 'このデータを閲覧する権限がありません');
        }

        $user = User::findOrFail($request->input('user_id'));

        $date = Carbon::parse($request->input('year_month'));

        $attendanceRecords = Attendance::with('rests')
            ->where('user_id', $user->id)
            ->whereYear('date', $date->year)
            ->whereMonth('date', $date->month)
            ->oldest('date')
            ->get();

        $fileName = sprintf('%sさんの勤怠_%s.csv', $user->name, $date->format('Y-m'));
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($attendanceRecords) {
            $file = fopen('php://output', 'w');

            fwrite($file, pack('C*', 0xEF, 0xBB, 0xBF));

            fputcsv($file, ['日付', '出勤', '退勤', '休憩', '合計']);

            foreach ($attendanceRecords as $record) {
                $breakTime = $record->total_break_time ? Carbon::parse($record->total_break_time)->format('G:i') : '';
                $totalTime = $record->total_time ? Carbon::parse($record->total_time)->format('G:i') : '';

                fputcsv($file, [
                    $record->date->isoFormat('MM/DD(ddd)'),
                    $record->clock_in ?: '',
                    $record->clock_out ?: '',
                    $breakTime,
                    $totalTime,
                ]);
            }

            fclose($file);
        }, 200, $headers);
    }
}