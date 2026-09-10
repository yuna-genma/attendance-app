<?php

namespace Database\Seeders;

use App\Models\Attendance;
use Illuminate\Database\Seeder;
use App\Models\Rest;

class RestSeeder extends Seeder
{

    public function run(): void
    {
        $attendances = Attendance::all();

        foreach ($attendances as $attendance) {
            Rest::factory()->create([
                'attendance_id' => $attendance->id,
                'created_at' => $attendance->created_at,
                'updated_at' => $attendance->updated_at,
            ]);
        }
    }
}
