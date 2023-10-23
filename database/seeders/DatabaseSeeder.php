<?php

namespace Database\Seeders;

use Database\Seeders\deployment\CountrySeeder as CountryDeploymentSeeder;
use Database\Seeders\deployment\DepartmentSeeder as DepartmentDeploymentSeeder;
use Database\Seeders\deployment\LevelSeeder as LevelDeploymentSeeder;
use Database\Seeders\deployment\UserSeeder as UserDeploymentSeeder;
use Database\Seeders\test\CategorySeeder;
use Database\Seeders\test\CountrySeeder;
use Database\Seeders\test\DepartmentSeeder;
use Database\Seeders\test\LevelSeeder;
use Database\Seeders\test\PrioritySeeder;
use Database\Seeders\test\TypeSeeder;
use Database\Seeders\test\UserSeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (\Illuminate\Support\Facades\App::environment('local')) {
            if (config('database.default') == 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=0');
            }
            DB::table('countries')->truncate();
            DB::table('types')->truncate();
            DB::table('levels')->truncate();
            DB::table('priorities')->truncate();
            DB::table('departments')->truncate();
            DB::table('categories')->truncate();
            DB::table('users')->truncate();
            DB::table('tickets')->truncate();
            // DB::table('ticket_chat')->truncate();
            DB::table('ticket_customer')->truncate();
            DB::table('ticket_technical_support')->truncate();
            DB::table('ticket_high_technical_support')->truncate();
            DB::table('ticket_histories')->truncate();
            // DB::table('blogs')->truncate();
            // DB::table('knowledges')->truncate();
            DB::table('contacts')->truncate();
            // DB::table('frequently_asked_question_groups')->truncate();
            // DB::table('frequently_asked_questions')->truncate();
            if (config('database.default') == 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            }
            $this->call(TypeSeeder::class);
            $this->call(PrioritySeeder::class);
            $this->call(CategorySeeder::class);
            $this->call(DepartmentSeeder::class);
            $this->call(CountrySeeder::class);
            $this->call(LevelSeeder::class);
            $this->call(UserSeeder::class);
        } else {
            $this->call(CountryDeploymentSeeder::class);
            $this->call(DepartmentDeploymentSeeder::class);
            $this->call(LevelDeploymentSeeder::class);
            $this->call(UserDeploymentSeeder::class);
        }
    }
}
