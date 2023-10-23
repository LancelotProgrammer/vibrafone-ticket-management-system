<?php

namespace Database\Seeders\deployment;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name'  => 'dev',
            'email'  => 'dev@dev.com',
            'password'  => Hash::make('cnau9eytf31bvw9xab78'),
            'email_verified_at' => now(),
            'company' => 'vibrafone',
            'department_id'  => 1,
            'level_id'  => 1,
            'country_id'  => 1,
            'blocked_at'  => null,
        ]);
    }
}
