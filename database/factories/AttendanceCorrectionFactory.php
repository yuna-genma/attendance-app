<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\Admin;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Enums\CorrectionStatus;

class AttendanceCorrectionFactory extends Factory
{

    public function definition(): array
    {
        $attendance = Attendance::factory()->create();

        return [
            'user_id' => $attendance->user_id,
            'admin_id' => Admin::factory(),
            'attendance_id' => $attendance->id,
            'approval_status' => fake()->randomElement(CorrectionStatus::cases())->value,
            'new_clock_in' => fake()->time('H:i:s', '11:00:00'),
            'new_clock_out' => fake()->time('H:i:s', '21:00:00'),
            'created_at' => fake()->dateTimeBetween($attendance->created_at, 'now'),
            'updated_at' => function (array $attributes) {
                return fake()->dateTimeBetween($attributes['created_at'], 'now');
            }
        ];
    }
}
