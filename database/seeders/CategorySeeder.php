<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        Category::create([
            'title' => 'c1',
            'description' => 'c1',
        ]);
        Category::create([
            'title' => 'c2',
            'description' => 'c2',
        ]);
        Category::create([
            'title' => 'c3',
            'description' => 'c3',
        ]);
    }
}
