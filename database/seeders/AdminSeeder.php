<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{

    public function run(): void
    {
        Admin::create([
            'name' => 'ユーザー3',
            'email' => 'user3@example.com',
            'password' => Hash::make('password'),
        ]);
    }
}
