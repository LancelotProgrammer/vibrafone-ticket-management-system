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
            'type_id' => 1,
        ]);
        Priority::create([
            'title' => 'p2',
            'description' => 'p2',
            'type_id' => 1,
        ]);
        Priority::create([
            'title' => 'p3',
            'description' => 'p3',
            'type_id' => 2,
        ]);
        Priority::create([
            'title' => 'p4',
            'description' => 'p4',
            'type_id' => 2,
        ]);
        Priority::create([
            'title' => 'p5',
            'description' => 'p5',
            'type_id' => 3,
        ]);
        Priority::create([
            'title' => 'p6',
            'description' => 'p6',
            'type_id' => 3,
        ]);
    }
}
