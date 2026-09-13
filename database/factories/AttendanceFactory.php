<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;


class AttendanceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => fn() => User::factory()->create()->id,
            'date' => $this->faker->date(),
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'attendance_status' => '退勤済',
        ];
    }
}
