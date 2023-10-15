<?php

namespace Database\Seeders;

use Database\Seeders\CountrySeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    private $truncateTablesFlag = false;
    private $seeder = false;

    public function run(): void
    {
        if (\Illuminate\Support\Facades\App::environment('local')) {
            if ($this->truncateTablesFlag) {
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
            if ($this->seeder) {
                $this->call(CountrySeeder::class);
                $this->call(PrioritySeeder::class);
                $this->call(TypeSeeder::class);
                $this->call(CategorySeeder::class);
                $this->call(DepartmentSeeder::class);
                $this->call(LevelSeeder::class);
                $this->call(UserSeeder::class);
            }
        }
    }
}
