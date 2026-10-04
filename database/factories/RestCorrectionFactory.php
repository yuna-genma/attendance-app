<?php

namespace Database\Factories;

use App\Models\RestCorrection;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\AttendanceCorrection;
use Carbon\Carbon;

class RestCorrectionFactory extends Factory
{
    protected $model = RestCorrection::class;

    public function definition(): array
    {
        $breakIn = Carbon::today()
            ->setHour(fake()->numberBetween(12, 13))
            ->setMinute(fake()->numberBetween(0, 59))
            ->setSecond(0);

        $breakOut = (clone $breakIn)->addMinutes(fake()->numberBetween(15, 60));

        return [
            'attendance_correction_id' => fn() => AttendanceCorrection::factory()->create()->id,
            'rest_id' => null,
            'new_break_in' => $breakIn->format('H:i:s'),
            'new_break_out' => $breakOut->format('H:i:s'),
            'created_at' => fake()->dateTimeBetween('-1 month', 'now'),
            'updated_at' => function (array $attributes) {
                return fake()->dateTimeBetween($attributes['created_at'], 'now');
            }
        ];
    }
}
