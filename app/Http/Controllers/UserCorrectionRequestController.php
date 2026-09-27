<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceCorrectionRequest;
use App\Models\Attendance;
use App\Enums\CorrectionStatus;
use App\Models\AttendanceCorrection;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class UserCorrectionRequestController extends Controller
{
    public function create(Request $request, $id)
    {
        $attendance = Attendance::with('rests')->findOrFail($id);
        $user = auth()->user();

        $breaks = $attendance->rests->map(function ($rest) {
            return [
                'break_in' => $rest->break_in ? Carbon::parse($rest->break_in)->format('H:i') : '',
                'break_out' => $rest->break_out ? Carbon::parse($rest->break_out)->format('H:i') : '',
            ];
        })->toArray();

        $statusValue = $request->query('status');

        $correction = null;
        if ($statusValue) {
            $correction = $attendance->attendanceCorrections()
                ->where('approval_status', $statusValue)
                ->latest()
                ->first();
        }

        $attendanceDate = $attendance->date_object;

        $data = [
            'id' => $attendance->id,
            'year' => $attendanceDate->format('Y年'),
            'date' => $attendanceDate->format('m月d日'),
            'clock_in' => $attendance->clock_in,
            'clock_out' => $attendance->clock_out,
            'comment' => $correction ? $correction->comment : null,
            'breaks' => $breaks,
            'application' => ($statusValue === CorrectionStatus::PENDING->value) ? $correction : null,
        ];

        return view('user.user-detail', compact('user', 'data'));
    }

    public function store(AttendanceCorrectionRequest $request, $id)
    {
        $attendance = Attendance::with('rests')->findOrFail($id);

        $validated = $request->validated();

        DB::transaction(function () use ($validated, $attendance) {
            $baseDate = $attendance->date_object->format('Y-m-d');

            $correction = $attendance->attendanceCorrections()->create([
                'user_id' => auth()->id(),
                'approved_by' => null,
                'new_clock_in' => Carbon::parse($baseDate . ' ' . $validated['new_clock_in']),
                'new_clock_out' => Carbon::parse($baseDate . ' ' . $validated['new_clock_out']),
                'comment' => $validated['comment'] ?? null,
                'approval_status' => CorrectionStatus::PENDING->value,
            ]);

            if (!empty($validated['new_break_in']) && is_array($validated['new_break_in'])) {
                $existRestIds = $attendance->rests->pluck('id')->toArray();

                foreach ($validated['new_break_in'] as $index => $breakIn) {
                    $breakOut = $validated['new_break_out'][$index] ?? null;

                    if ($breakIn && $breakOut) {
                        $restId = $existRestIds[$index] ?? null;

                        $correction->restCorrections()->create([
                            'rest_id' => $restId,
                            'new_break_in' => Carbon::parse($baseDate . ' ' . $breakIn),
                            'new_break_out' => Carbon::parse($baseDate . ' ' . $breakOut),
                        ]);
                    }
                }
            }
        });

        return redirect('/attendance/list');
    }

    public function userApplicationIndex()
    {
        $user = auth()->user();

        $corrections = AttendanceCorrection::with('attendance')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $formattedApplications = $corrections->map(function ($correction) {
            return [
                'id' => $correction->id,
                'approval_status' => $correction->approval_status->value,
                'date' => $correction->attendance->date_object->format('Y/m/d'),
                'comment' => $correction->comment,
                'application_date' => $correction->created_at->format('Y/m/d'),
            ];
        });

        return view('user.user-application-list', compact('user', 'formattedApplications'));
    }

}
