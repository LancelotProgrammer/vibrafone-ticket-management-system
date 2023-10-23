<?php

namespace Database\Seeders\test;

use App\Models\Type;
use Illuminate\Database\Seeder;

class TypeSeeder extends Seeder
{
    public function run(): void
    {
        Type::create([
            'title' => 't1',
            'description' => 't1',
        ]);
        Type::create([
            'title' => 't2',
            'description' => 't2',
        ]);
        Type::create([
            'title' => 't3',
            'description' => 't3',
        ]);
    }
}
