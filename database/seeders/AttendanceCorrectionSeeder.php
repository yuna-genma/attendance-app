<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use Illuminate\Database\Seeder;
use App\Models\Admin;

class AttendanceCorrectionSeeder extends Seeder
{
    public function run(): void
    {
        $attendances = Attendance::all();
        $admins = Admin::all();

        if ($admins->isEmpty()) {
            return;
        }

        foreach ($attendances as $attendance) {
            if (fake()->boolean(30)) {
                AttendanceCorrection::factory()->create([
                    'attendance_id' => $attendance->id,
                    'user_id' => $attendance->user_id,
                    'admin_id' => $admins->random()->id,
                ]);
            }
        }
    }
}
