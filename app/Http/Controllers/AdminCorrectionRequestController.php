<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\AttendanceCorrection;
use Illuminate\Support\Fluent;
use Illuminate\Support\Facades\DB;

class AdminCorrectionRequestController extends Controller
{
    public function adminApplicationIndex()
    {
        if (!auth()->user()->admin_status) {
            abort(403, 'このデータを閲覧する権限がありません');
        }

        $applications = AttendanceCorrection::whereHas('attendance')
            ->with(['user', 'attendance'])
            ->get();

        $applications->each(function ($application) {
            if ($application->attendance) {

                $application->setRelation('AttendanceRecord', $application->attendance);
            }
        });

        return view('admin.admin-application-list', compact('applications'));
    }

    public function showApplication(Request $request, $attendance_correct_request_id)
    {
        if (!auth()->user()->admin_status) {
            abort(403, 'このデータを閲覧する権限がありません');
        }

        $attendanceCorrection = AttendanceCorrection::with(['user', 'attendance', 'restCorrections'])
            ->findOrFail($attendance_correct_request_id);

        $user = $attendanceCorrection->user;

        $newBreaks = $attendanceCorrection->restCorrections->map(function ($rest) {
            return (object) [
                'break_in' => $rest->new_break_in,
                'break_out' => $rest->new_break_out,
            ];
        });

        $newDate = $attendanceCorrection->attendance->date;

        $newClockIn = $attendanceCorrection->new_clock_in ? Carbon::parse($attendanceCorrection->new_clock_in)->format('H:i') : ' ';
        $newClockOut = $attendanceCorrection->new_clock_out ? Carbon::parse($attendanceCorrection->new_clock_out)->format('H:i') : ' ';

        $application = new Fluent(
            [
                'id' => $attendanceCorrection->id,
                'new_date' => $newDate,
                'new_clock_in' => $newClockIn,
                'new_clock_out' => $newClockOut,
                'comment' => $attendanceCorrection->comment ?? null,
                'proposalBreaks' => $newBreaks,
                'approval_status' => $attendanceCorrection->approval_status,
            ]
        );

        return view('admin.admin-application-detail', compact('user', 'application'));
    }

    public function approve(Request $request, $attendance_correct_request_id)
    {
        if (!auth()->user()->admin_status) {
            abort(403, 'このデータを操作する権限がありません');
        }

        $attendanceCorrection = AttendanceCorrection::with(['attendance', 'restCorrections'])
            ->findOrFail($attendance_correct_request_id);

        if ($attendanceCorrection->approval_status === '承認済み') {
            return redirect()->back()->with('error', 'この申請は既に承認されています。');
        }

        DB::transaction(function () use ($attendanceCorrection) {
            DB::table('attendances')
                ->where('id', $attendanceCorrection->attendance_id)
                ->update([
                    'clock_in' => $attendanceCorrection->new_clock_in,
                    'clock_out' => $attendanceCorrection->new_clock_out,
                    'updated_at' => now(),
                ]);

            $restCorrections = DB::table('rest_corrections')
                ->where('attendance_correction_id', $attendanceCorrection->id)
                ->get();

            $activeRestIds = [];

            foreach ($restCorrections as $restCorrection) {
                if ($restCorrection->rest_id) {
                    DB::table('rests')
                        ->where('id', $restCorrection->rest_id)
                        ->update([
                            'break_in' => $restCorrection->new_break_in,
                            'break_out' => $restCorrection->new_break_out,
                            'updated_at' => now(),
                        ]);
                    $activeRestIds[] = $restCorrection->rest_id;
                } else {
                    $newRestId = DB::table('rests')->insertGetId([
                        'attendance_id' => $attendanceCorrection->attendance_id,
                        'break_in' => $restCorrection->new_break_in,
                        'break_out' => $restCorrection->new_break_out,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $activeRestIds[] = $newRestId;

                    DB::table('rest_corrections')
                        ->where('id', $restCorrection->id)
                        ->update(['rest_id' => $newRestId]);
                }
            }

            DB::table('rests')
                ->where('attendance_id', $attendanceCorrection->attendance_id)
                ->whereNotIn('id', $activeRestIds)
                ->delete();

            DB::table('attendance_corrections')
                ->where('id', $attendanceCorrection->id)
                ->update([
                    'approval_status' => '承認済み',
                    'approved_by' => auth()->id(),
                    'updated_at' => now(),
                ]);
        });

        return redirect()->back()->with('success', '勤怠修正申請を承認しました');
    }
}
