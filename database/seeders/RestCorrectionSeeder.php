<?php

namespace Database\Seeders;

use App\Models\AttendanceCorrection;
use Carbon\Carbon;
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
            if (fake()->boolean(80)) {
                $breakCount = fake()->numberBetween(1, 2);

                $nextBreakIn = Carbon::today()
                    ->setHour(fake()->numberBetween(11, 12))
                    ->setMinute(fake()->numberBetween(0, 59))
                    ->setSecond(0);

                for ($i = 0; $i < $breakCount; $i++) {

                    if ($i > 0) {
                        $nextBreakIn->addMinutes(fake()->numberBetween(15, 45));
                    }

                    $nextBreakOut = (clone $nextBreakIn)->addMinutes(fake()->numberBetween(15, 45));

                    $existingRest = Rest::where('attendance_id', $correction->attendance_id)
                        ->inRandomOrder()->first();

                    RestCorrection::factory()->create([
                        'attendance_correction_id' => $correction->id,
                        'rest_id' => $existingRest ? $existingRest->id : null,
                        'new_break_in' => $nextBreakIn->format('H:i:s'),
                        'new_break_out' => $nextBreakOut->format('H:i:s'),
                    ]);

                    $nextBreakIn = clone $nextBreakOut;
                }
            }
        }
    }
}
