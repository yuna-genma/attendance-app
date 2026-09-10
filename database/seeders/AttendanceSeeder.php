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
        $users = User::all();

        foreach ($users as $user) {
            for ($i = 0; $i < 15; $i++) {
                $date = Carbon::now()->subDays($i + fake()->numberBetween(0, 2));

                Attendance::factory()->create([
                    'user_id' => $user->id,
                    'date' => $date->format('Y-m-d'),
                    'created_at' => $date,
                    'updated_at' => $date,
                ]);
            }
        }
    }
}
