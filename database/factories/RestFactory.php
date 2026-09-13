<?php

namespace Database\Factories;

use App\Models\Attendance;
use Illuminate\Database\Eloquent\Factories\Factory;

class RestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'attendance_id' => fn() => Attendance::factory()->create()->id,
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ];
    }
}