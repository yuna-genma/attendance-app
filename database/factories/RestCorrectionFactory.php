<?php

namespace Database\Factories;

use App\Models\Rest;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Enums\CorrectionStatus;

class RestCorrectionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'attendance_correction_id' => fn() => Rest::factory()->create()->id,
            'approval_status' => fake()->randomElement(CorrectionStatus::cases())->value,
            'new_break_in' => fake()->time('H:i:s', '12:15:00'),
            'new_break_out' => fake()->time('H:i:s', '13:15:00'),
            'created_at' => fake()->dateTimeBetween('-1 month', 'now'),
            'updated_at' => function (array $attributes) {
                return fake()->dateTimeBetween($attributes['created_at'], 'now');
            }
        ];
    }
}
