<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        Department::create([
            'title' => 'default',
            'code' => 'D',
            'description' => 'This is the default department for non support users',
        ]);
        Department::create([
            'title' => 'd1',
            'code' => 'd1',
            'description' => 'd1',
        ]);
        Department::create([
            'title' => 'd2',
            'code' => 'd2',
            'description' => 'd2',
        ]);
        Department::create([
            'title' => 'd3',
            'code' => 'd3',
            'description' => 'd3',
        ]);
    }
}
