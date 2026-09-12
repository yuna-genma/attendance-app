<?php

namespace Database\Factories;

use App\Models\Admin;
use App\Models\Rest;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Enums\CorrectionStatus;

class RestCorrectionFactory extends Factory
{
    public function definition(): array
    {
        $rest = Rest::factory()->create();

        return [
            'rest_id' => $rest->id,
            'admin_id' => Admin::factory(),
            'approval_status' => fake()->randomElement(CorrectionStatus::cases())->value,
            'new_break_in' => fake()->time('H:i:s', '12:00:00'),
            'new_break_out' => fake()->time('H:i:s', '13:00:00'),
            'created_at' => fake()->dateTimeBetween($rest->created_at, 'now'),
            'updated_at' => function (array $attributes) {
                return fake()->dateTimeBetween($attributes['created_at'], 'now');
            }
        ];
    }
}
