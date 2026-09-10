<?php

namespace Database\Factories;

use App\Models\Attendance;
use Illuminate\Database\Eloquent\Factories\Factory;

class RestFactory extends Factory
{
    public function definition(): array
    {
        $attendance = Attendance::factory()->create();

        return [
            'attendance_id' => $attendance->id,
            'break_in' => fake()->time('H:i:s', '13:00:00'),
            'break_out' => fake()->time('H:i:s', '14:00:00'),
            'created_at' => $attendance->created_at,
            'updated_at' => $attendance->updated_at,
        ];
    }
}
