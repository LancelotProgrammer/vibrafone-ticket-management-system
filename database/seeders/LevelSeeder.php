<?php

namespace Database\Seeders;

use App\Models\Level;
use Illuminate\Database\Seeder;

class LevelSeeder extends Seeder
{
    public function run(): void
    {
        Level::create([
            'title' => 'level 0',
            'code' => 0,
            'description' => 'This is the default level for non support users',
        ]);
        Level::create([
            'title' => 'level 1',
            'code' => 1,
            'description' => 'This is normal level support',
        ]);
        Level::create([
            'title' => 'level 2',
            'code' => 2,
            'description' => 'This is high level support',
        ]);
    }
}
