<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceCorrectionFactory extends Factory
{
    protected $model = AttendanceCorrection::class;

    public function definition(): array
    {
        $status = fake()->randomElement(['承認待ち', '承認済み']);

        $approvedBy = ($status === '承認待ち')
            ? null
            : fn() => User::where('admin_status', true)->inRandomOrder()->first()?->id
                ?? User::factory()->create(['admin_status' => true])->id;

        $clockInHour = fake()->numberBetween(7, 10);
        $clockIn = sprintf('%02d:%02d:00', $clockInHour, fake()->numberBetween(0, 59));

        $clockOutHour = $clockInHour + fake()->numberBetween(9, 11);
        $clockOut = sprintf('%02d:%02d:00', $clockOutHour, fake()->numberBetween(0, 59));

        return [
            'user_id' => fn() => User::where('admin_status', false)->inRandomOrder()->first()?->id
                ?? User::factory()->create(['admin_status' => false])->id,
            'approved_by' => $approvedBy,
            'attendance_id' => fn() => Attendance::inRandomOrder()->first()?->id
                ?? Attendance::factory()->create()->id,
            'approval_status' => $status,
            'new_clock_in' => $clockIn,
            'new_clock_out' => $clockOut,
            'comment' => fake()->randomElement([
                '打刻漏れ',
                '修正あり',
                '入力ミス',
                '直行直帰',
                '打刻忘れ'
            ]),
            'created_at' => fake()->dateTimeBetween('-1 month', 'now'),
            'updated_at' => function (array $attributes) use ($status) {
                if ($status === '承認待ち') {
                    return $attributes['created_at'];
                }
                return fake()->dateTimeBetween($attributes['created_at'], 'now');
            }
        ];
    }
}
