<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Rest;
use App\Models\Admin;
use App\Models\RestCorrection;
class RestCorrectionSeeder extends Seeder
{

    public function run(): void
    {
        $admins = Admin::all();
        $rests = Rest::all();

        if ($admins->isEmpty()) {
            return;
        }

        foreach ($rests as $rest) {
            if (fake()->boolean(25)) {
                RestCorrection::factory()->create([
                    'rest_id' => $rest->id,
                    'admin_id' => $admins->random()->id,
                ]);
            }
        }
    }
}
