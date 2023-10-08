<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name'  => 'admin',
            'email'  => 'admin@admin.com',
            'password'  => Hash::make('admin123'),
            'department_id'  => 1,
            'country_id'  => 200,
            'blocked_at'  => null,
            'approved_at'  => now(),
        ]);
    }
}
