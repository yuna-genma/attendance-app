<?php

namespace Database\Factories;

use App\Models\User;
use App\Enums\AttendanceStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceFactory extends Factory
{
    public function definition(): array
    {
        $date = fake()->dateTimeBetween('-1month', 'now');

        $clockInHour = fake()->numberBetween(7, 10);
        $clockInMinute = fake()->numberBetween(0, 59);
        $clockInSecond = fake()->numberBetween(0, 59);
        $clockIn = sprintf('%02d:%02d:%02d', $clockInHour, $clockInMinute, $clockInSecond);

        return [
            'user_id' => User::factory(),
            'date' => $date->format('Y-m-d'),
            'clock_in' => $clockIn,
            'clock_out' => function () {
                $clockOutHour = fake()->numberBetween(17, 22);
                $clockOutMinute = fake()->numberBetween(0, 59);
                $clockOutSecond = fake()->numberBetween(0, 59);
                return sprintf('%02d:%02d:%02d', $clockOutHour, $clockOutMinute, $clockOutSecond);
            },
            'attendance_status' => fake()->randomElement(AttendanceStatus::cases())->value,
            'created_at' => $date,
            'updated_at' => $date,
        ];
    }
}
