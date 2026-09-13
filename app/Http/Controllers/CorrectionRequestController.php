<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceCorrectionRequest;
use App\Models\Attendance;
use App\Models\AttendanceCorrect;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CorrectionRequestController extends Controller
{
    public function create($id)
    {
        $attendance = Attendance::findOrFail($id);

        $user = auth()->user();

        $breaks = $attendance->rests->map(function ($rest) {
            return [
                'break_in' => $rest->break_in ? Carbon::parse($rest->break_in)->format('H:i') : '',
                'break_out' => $rest->break_out ? Carbon::parse($rest->break_out)->format('H:i') : '',
            ];
        })->toArray();

        $pendingCorrection = $attendance->attendanceCorrections()
            ->where('approval_status', '承認待ち')
            ->first();

        $attendanceDate = $attendance->date_object;

        $data = [
            'id' => $attendance->id,
            'year' => $attendanceDate->format('Y年'),
            'date' => $attendanceDate->format('m月d日'),
            'clock_in' => $attendance->clock_in ? Carbon::parse($attendance->clock_in)->format('H:i') : '',
            'clock_out' => $attendance->clock_out ? Carbon::parse($attendance->clock_out)->format('H:i') : '',
            'comment' => $pendingCorrection ? $pendingCorrection->comment : null,
            'breaks' => $breaks,
            'application' => $pendingCorrection ? $pendingCorrection : null,
        ];

        return view('user.user-detail', compact('user', 'data'));
    }

    public function store(AttendanceCorrectionRequest $request, $id)
    {
        $attendance = Attendance::findOrFail($id);
        $request->validated();

        DB::transaction(function () use ($request, $attendance) {
            $baseDate = $attendance->date_object->format('Y-m-d');

            $correction = $attendance->attendanceCorrections()->create([
                'user_id' => auth()->id(),
                'admin_id' => null,
                'clock_in' => Carbon::parse($baseDate . '' . $request->new_clock_in),
                'clock_out' => Carbon::parse($baseDate . '' . $request->new_clock_out),
                'comment' => $request->comment,
                'approval_status' => '承認待ち',
            ]);

            if ($request->has('new_clock_in')) {
                $existRestIds = $attendance->rests->pluck('id')->toArray();

                foreach ($request->new_break_in as $index => $breakIn) {
                    $breakOut = $request->new_break_out[$index] ?? null;

                    if ($breakIn && $breakOut) {
                        $restId = $existRestIds[$index] ?? null;
                        $correction->restCorrections()->create([
                            'rest_id' => $restId,
                            'break_in' => Carbon::parse($baseDate . '' . $breakIn),
                            'break_out' => Carbon::parse($baseDate . '' . $breakOut),
                        ]);
                    }
                }
            }
        });

        return redirect('/attendance/list');
    }

    /**
     * Display the specified resource.
     */
    public function show(AttendanceCorrect $attendanceCorrect)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(AttendanceCorrect $attendanceCorrect)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAttendanceCorrectRequest $request, AttendanceCorrect $attendanceCorrect)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AttendanceCorrect $attendanceCorrect)
    {
        //
    }
}
