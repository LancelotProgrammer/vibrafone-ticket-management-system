<?php

namespace App\Console\Commands\Update;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateTicketsStatusesAndHandlers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-tickets-statuses-and-handlers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command will update the tickets statuses and tickets handlers names from the old version to the new version.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        DB::table('ticket_histories')
            ->where('status', 'like', '%Hight Level Support Pending%')
            ->update(['status' => 'High Technical Support Pending']);
        DB::table('tickets')
            ->where('status', 'like', '%Hight Level Support Pending%')
            ->update(['status' => 'High Technical Support Pending']);

        DB::table('ticket_histories')
            ->where('status', 'like', '%Technical_Support Under Monitoring%')
            ->update(['status' => 'Technical Support Under Monitoring']);
        DB::table('tickets')
            ->where('status', 'like', '%Technical_Support Under Monitoring%')
            ->update(['status' => 'Technical Support Under Monitoring']);

        DB::table('ticket_histories')
            ->where('handler', 'like', '%High level support%')
            ->update(['handler' => 'High Technical Support']);
        DB::table('tickets')
            ->where('handler', 'like', '%High level support%')
            ->update(['handler' => 'High Technical Support']);
    }
}
