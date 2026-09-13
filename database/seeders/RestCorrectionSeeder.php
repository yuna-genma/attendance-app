<?php

namespace Database\Seeders;

use App\Models\AttendanceCorrection;
use Illuminate\Database\Seeder;
use App\Models\Rest;
use App\Models\RestCorrection;
class RestCorrectionSeeder extends Seeder
{

    public function run(): void
    {
        $attendanceCorrections = AttendanceCorrection::all();

        if ($attendanceCorrections->isEmpty()) {
            return;
        }

        foreach ($attendanceCorrections as $correction) {
            if (fake()->boolean(25)) {
                $existingRest = Rest::where('attendance_id', $correction->attendance_id)
                    ->inRandomOrder()->first();

                RestCorrection::factory()->create([
                    'attendance_correction_id' => $correction->id,
                    'rest_id' => $existingRest ? $existingRest->id : null,
                ]);
            }
        }
    }
}
