<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name'  => 'admin',
            'email'  => 'admin@admin.com',
            'password'  => Hash::make('admin123'),
            'email_verified_at' => now(),
            'department_id'  => 1,
            'level_id'  => 1,
            'country_id'  => 200,
            'blocked_at'  => null,
        ]);
        User::create([
            'name'  => 'mod',
            'email'  => 'mod@mod.com',
            'password'  => Hash::make('mod123'),
            'email_verified_at' => now(),
            'department_id'  => 1,
            'level_id'  => 1,
            'country_id'  => 200,
            'blocked_at'  => null,
        ]);

        User::create([
            'name'  => 'sup1',
            'email'  => 'sup1@sup1.com',
            'password'  => Hash::make('sup1123'),
            'email_verified_at' => now(),
            'department_id'  => 2,
            'level_id'  => 2,
            'country_id'  => 200,
            'blocked_at'  => null,
        ]);
        User::create([
            'name'  => 'sup2',
            'email'  => 'sup2@sup2.com',
            'password'  => Hash::make('sup2123'),
            'email_verified_at' => now(),
            'department_id'  => 2,
            'level_id'  => 3,
            'country_id'  => 200,
            'blocked_at'  => null,
        ]);

        User::create([
            'name'  => 'sup3',
            'email'  => 'sup3@sup3.com',
            'password'  => Hash::make('sup3123'),
            'email_verified_at' => now(),
            'department_id'  => 2,
            'level_id'  => 2,
            'country_id'  => 200,
            'blocked_at'  => null,
        ]);
        User::create([
            'name'  => 'sup4',
            'email'  => 'sup4@sup4.com',
            'password'  => Hash::make('sup4123'),
            'email_verified_at' => now(),
            'department_id'  => 2,
            'level_id'  => 3,
            'country_id'  => 200,
            'blocked_at'  => null,
        ]);

        User::create([
            'name'  => 'sup5',
            'email'  => 'sup5@sup5.com',
            'password'  => Hash::make('sup5123'),
            'email_verified_at' => now(),
            'department_id'  => 3,
            'level_id'  => 2,
            'country_id'  => 200,
            'blocked_at'  => null,
        ]);
        User::create([
            'name'  => 'sup6',
            'email'  => 'sup6@sup6.com',
            'password'  => Hash::make('sup6123'),
            'email_verified_at' => now(),
            'department_id'  => 3,
            'level_id'  => 3,
            'country_id'  => 200,
            'blocked_at'  => null,
        ]);

        User::create([
            'name'  => 'sup7',
            'email'  => 'sup7@sup7.com',
            'password'  => Hash::make('sup7123'),
            'email_verified_at' => now(),
            'department_id'  => 3,
            'level_id'  => 2,
            'country_id'  => 200,
            'blocked_at'  => null,
        ]);
        User::create([
            'name'  => 'sup8',
            'email'  => 'sup8@sup8.com',
            'password'  => Hash::make('sup8123'),
            'email_verified_at' => now(),
            'department_id'  => 3,
            'level_id'  => 3,
            'country_id'  => 200,
            'blocked_at'  => null,
        ]);

        User::create([
            'name'  => 'cus1',
            'email'  => 'cus1@cus1.com',
            'password'  => Hash::make('cus1123'),
            'email_verified_at' => now(),
            'department_id'  => 2,
            'level_id'  => 1,
            'country_id'  => 200,
            'blocked_at'  => null,
        ]);
        User::create([
            'name'  => 'cus2',
            'email'  => 'cus2@cus2.com',
            'password'  => Hash::make('cus2123'),
            'email_verified_at' => now(),
            'department_id'  => 2,
            'level_id'  => 1,
            'country_id'  => 200,
            'blocked_at'  => null,
        ]);

        User::create([
            'name'  => 'cus3',
            'email'  => 'cus3@cus3.com',
            'password'  => Hash::make('cus3123'),
            'email_verified_at' => now(),
            'department_id'  => 3,
            'level_id'  => 1,
            'country_id'  => 200,
            'blocked_at'  => null,
        ]);
        User::create([
            'name'  => 'cus4',
            'email'  => 'cus4@cus4.com',
            'password'  => Hash::make('cus4123'),
            'email_verified_at' => now(),
            'department_id'  => 3,
            'level_id'  => 1,
            'country_id'  => 200,
            'blocked_at'  => null,
        ]);
    }
}
