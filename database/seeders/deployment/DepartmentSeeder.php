<?php

namespace Database\Seeders\deployment;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        Department::create([
            'title' => 'default',
            'code' => 'Default',
            'description' => 'This is the default department for non support users',
        ]);
    }
}
