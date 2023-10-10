<?php

namespace Database\Seeders;

use App\Models\Priority;
use Illuminate\Database\Seeder;

class PrioritySeeder extends Seeder
{
    public function run(): void
    {
        Priority::create([
            'title' => 'p1',
            'description' => 'p1',
        ]);
        Priority::create([
            'title' => 'p2',
            'description' => 'p2',
        ]);
        Priority::create([
            'title' => 'p3',
            'description' => 'p3',
        ]);
    }
}
