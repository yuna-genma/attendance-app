<?php

namespace Database\Seeders;

use App\Enums\CorrectionStatus;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use App\Models\User;

class AttendanceCorrectionSeeder extends Seeder
{
    public function run(): void
    {
        $admins = User::where('admin_status', true)->get();

        $attendanceGroups = Attendance::all()->groupBy(function ($attendance) {
            return $attendance->dateObject?->format('Y-m') ?? 'unknown';
        });

        foreach ($attendanceGroups as $month => $attendances) {
            $count = min(3, $attendances->count());
            $selectedAttendances = $attendances->random($count);

            foreach ($selectedAttendances as $attendance) {
                $status = fake()->randomElement(['承認待ち', '承認済み']);

                $approvedBy = ($status === '承認待ち')
                    ? null
                    : $admins->random()?->id;

                AttendanceCorrection::factory()->create([
                    'attendance_id' => $attendance->id,
                    'user_id' => $attendance->user_id,
                    'approved_by' => $approvedBy,
                    'approval_status' => $status,
                ]);
            }


        }
    }
}
