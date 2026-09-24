<?php

namespace Database\Seeders;

use App\Enums\CorrectionStatus;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use Illuminate\Database\Seeder;
use App\Models\User;

class AttendanceCorrectionSeeder extends Seeder
{
    public function run(): void
    {
        $attendances = Attendance::all();

        $admins = User::where('admin_status', true)->get();

        foreach ($attendances as $attendance) {
            if (fake()->boolean(30)) {
                $status = fake()->randomElement(CorrectionStatus::cases())->value;

                $approvedBy = ($status === CorrectionStatus::PENDING->value)
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
