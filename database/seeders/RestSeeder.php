<?php

namespace Database\Seeders;

use App\Models\Attendance;
use Illuminate\Database\Seeder;
use App\Models\Rest;

class RestSeeder extends Seeder
{

    public function run(): void
    {
        Attendance::query()->chunkById(100, function ($attendances): void {
            foreach ($attendances as $attendance) {
                Rest::updateOrCreate([
                    'attendance_id' => $attendance->id
                ], [
                    'break_in' => '12:00:00',
                    'break_out' => '13:00:00',
                ]);
            }
        });
    }
}