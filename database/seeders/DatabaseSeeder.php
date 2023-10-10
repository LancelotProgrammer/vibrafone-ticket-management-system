<?php

namespace Database\Seeders;

use Database\Seeders\AdminSeeder;
use Database\Seeders\CountrySeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    private $truncateTablesFlag = true;

    public function run(): void
    {
        if ($this->truncateTablesFlag) {
            if (\Illuminate\Support\Facades\App::environment('local')) {
                if (config('database.default') == 'mysql') {
                    DB::statement('SET FOREIGN_KEY_CHECKS=0');
                }

                // DB::table('blogs')->truncate();
                DB::table('categories')->truncate();
                DB::table('contacts')->truncate();
                DB::table('countries')->truncate();
                DB::table('departments')->truncate();
                DB::table('levels')->truncate();
                // DB::table('frequently_asked_question_groups')->truncate();
                // DB::table('frequently_asked_questions')->truncate();
                // DB::table('knowledges')->truncate();
                DB::table('priorities')->truncate();
                // DB::table('ticket_chat')->truncate();
                DB::table('ticket_history')->truncate();
                DB::table('tickets')->truncate();
                DB::table('types')->truncate();

                // DB::table('migrations')->truncate();
                // DB::table('personal_access_tokens')->truncate();
                // DB::table('settings')->truncate();
                // DB::table('jobs')->truncate();
                // DB::table('failed_jobs')->truncate();
                // DB::table('notifications')->truncate();

                if (config('database.default') == 'mysql') {
                    DB::statement('SET FOREIGN_KEY_CHECKS=1');
                }
            }
        }

        $this->call(CountrySeeder::class);
        $this->call(PrioritySeeder::class);
        $this->call(TypeSeeder::class);
        $this->call(CategorySeeder::class);
        $this->call(DepartmentSeeder::class);
        $this->call(LevelSeeder::class);
        $this->call(UserSeeder::class);
    }
}

// php artisan make:model Blog
// php artisan make:model Category
// php artisan make:model Contact
// php artisan make:model Country
// php artisan make:model Department
// php artisan make:model Level
// php artisan make:model FrequentlyAskedQuestionGroup
// php artisan make:model FrequentlyAskedQuestion
// php artisan make:model Knowledge
// php artisan make:model Permission
// php artisan make:model Priority
// php artisan make:model Role
// php artisan make:model Ticket
// php artisan make:model Type
// php artisan make:model User
