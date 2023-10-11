<?php

namespace App\Filament\Resources\TicketResource\Pages;

use App\Enums\TicketHandler;
use App\Enums\TicketStatus;
use App\Filament\Resources\TicketResource;
use App\Mail\TicketCreated;
use App\Models\Level;
use App\Models\Ticket;
use App\Models\TicketHistory;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Mail;

class CreateTicket extends CreateRecord
{
    protected static string $resource = TicketResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        //NOTE: default data for ticket
        $data['status'] = TicketStatus::IN_PROGRESS->value;
        $data['handler'] = TicketHandler::TECHNICAL_SUPPORT->value;
        $data['level_id'] = Level::where('code', 1)->first()->id;
        return $data;
    }

    protected function afterCreate(): void
    {
        Mail::to(User::find($this->record->customer_user_id))->send(new TicketCreated());//send to admin
        Mail::to(User::find($this->record->customer_user_id))->send(new TicketCreated());//send to high level support
        Mail::to(User::find($this->record->customer_user_id))->send(new TicketCreated());//send to customer
        $ticket = $this->record;
        $ticket->customer()->attach(auth()->user()->id);
        $ticketHistory = new TicketHistory([
            'title' => 'ticket has been created',
            'body' => 'ticket has been created',
            'owner' => auth()->user()->email,
            'work_order' => null,
            'sub_work_order' => null,
            'status' => TicketStatus::IN_PROGRESS->value,
            'handler' => TicketHandler::TECHNICAL_SUPPORT->value,
            'created_at' => now(),
        ]);
        $ticket->ticketHistory()->save($ticketHistory);
        $ticket->save();
    }
}
