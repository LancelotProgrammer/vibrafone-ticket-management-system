<?php

namespace App\Console\Commands\Update;

use App\Models\Ticket;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UdpateTicketsEscalationTime extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-tickets-escalation-time';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command will update the escalation time of tickets from ticket_histories table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $ticketHistories = DB::table('ticket_histories')
            ->where('title', 'like', '%Ticket has been escalated to%')
            ->get()
            ->toArray();
        foreach ($ticketHistories as $ticketHistory) {
            $ticket = Ticket::find($ticketHistory->ticket_id);
            $ticket->escalated_at = $ticketHistory->created_at;
            $ticket->save();
        }
    }
}
