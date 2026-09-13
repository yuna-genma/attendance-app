<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceSeeder extends Seeder
{

    public function run(): void
    {
        $now = Carbon::now('Asia/Tokyo')->startOfDay();
        $user1 = User::where('email', 'user1@example.com')->first();

        $generalUsers = User::whereKeyNot($user1->id)->get();

        foreach ($generalUsers as $user) {
            $this->createRegularAttendances(
                user: $user,
                startDate: $now->copy()->subMonths(5)->startOfMonth(),
                endDate: $now
            );
        }

        for ($monthsAgo = 5; $monthsAgo >= 1; $monthsAgo--) {
            $targetMonth = $now->copy()->subMonths($monthsAgo);

            $this->createFirstWeekdaysOfMonth(
                user: $user1,
                targetMonth: $targetMonth,
                numberOfDays: 15,
            );
        }

        $this->createIntentionalAttendances(
            user: $user1,
            targetMonth: $now,
            today: $now,
        );
    }

    private function createRegularAttendances(
        User $user,
        Carbon $startDate,
        Carbon $endDate
    ): void {
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            $this->createAttendance(
                userId: $user->id,
                date: $currentDate,
                clockIn: '09:00',
                clockOut: '18:00',
            );
            $currentDate->addDay();
        }
    }

    private function createFirstWeekdaysOfMonth(
        User $user,
        Carbon $targetMonth,
        int $numberOfDays,
    ): void {
        $currentDate = $targetMonth->copy()->startOfMonth();
        $createdCount = 0;

        while (
            $currentDate->month === $targetMonth->month
            && $createdCount < $numberOfDays
        ) {
            if ($currentDate->isWeekday()) {
                $this->createAttendance(
                    userId: $user->id,
                    date: $currentDate,
                    clockIn: '09:00',
                    clockOut: '18:00',
                );

                $createdCount++;
            }

            $currentDate->addDay();
        }
    }
    private function createIntentionalAttendances(
        User $user,
        Carbon $targetMonth,
        Carbon $today,
    ): void {
        $patterns = [
            ...array_fill(0, 10, [
                'clock_in' => '09:00',
                'clock_out' => '18:00',
            ]),
            ...array_fill(0, 3, [
                'clock_in' => '09:00',
                'clock_out' => '20:00',
            ]),
            ...array_fill(0, 2, [
                'clock_in' => '09:30',
                'clock_out' => '18:00',
            ]),
            [
                'clock_in' => '09:00',
                'clock_out' => '17:00',
            ],
            [
                'clock_in' => '08:00',
                'clock_out' => '21:00',
            ],
        ];

        $currentDate = $targetMonth->copy()->startOfMonth();

        foreach ($patterns as $pattern) {
            while (
                $currentDate->month === $targetMonth->month
                && !$currentDate->isWeekday()
            ) {
                $currentDate->addDay();
            }

            if (
                $currentDate->month !== $targetMonth->month
                || $currentDate->gt($today)
            ) {
                break;
            }

            $this->createAttendance(
                userId: $user->id,
                date: $currentDate,
                clockIn: $pattern['clock_in'],
                clockOut: $pattern['clock_out'],
            );

            $currentDate->addDay();
        }
    }

    private function createAttendance(
        int $userId,
        Carbon $date,
        string $clockIn,
        string $clockOut,
    ): void {
        Attendance::updateOrCreate(
            [
                'user_id' => $userId,
                'date' => $date->toDateString(),
            ],
            [
                'clock_in' => $clockIn,
                'clock_out' => $clockOut,
                'attendance_status' => '退勤済',
            ],
        );
    }
}
