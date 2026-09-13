<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\Admin;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Enums\CorrectionStatus;

class AttendanceCorrectionFactory extends Factory
{

    public function definition(): array
    {

        return [
            'user_id' => fn() => User::factory()->create()->id,
            'admin_id' => fn() => Admin::factory()->create()->id,
            'attendance_id' => fn() => Attendance::factory()->create()->id,
            'approval_status' => fake()->randomElement(CorrectionStatus::cases())->value,
            'new_clock_in' => fake()->time('H:i:s', '11:00:00'),
            'new_clock_out' => fake()->time('H:i:s', '21:00:00'),
            'created_at' => fake()->dateTimeBetween('-1 month', 'now'),
            'updated_at' => function (array $attributes) {
                return fake()->dateTimeBetween($attributes['created_at'], 'now');
            }
        ];
    }
}
