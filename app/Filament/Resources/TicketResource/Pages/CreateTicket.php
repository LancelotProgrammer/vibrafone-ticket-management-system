<?php

namespace App\Filament\Resources\TicketResource\Pages;

use App\Enums\TicketHandler;
use App\Enums\TicketStatus;
use App\Filament\Resources\TicketResource;
use App\Mail\TicketCreated;
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
        $data['customer_user_id'] = auth()->id();
        $data['status'] = TicketStatus::IN_PROGRESS->value;
        $data['handler'] = TicketHandler::TECHNICAL_SUPPORT->value;
        return $data;
    }

    protected function afterCreate(): void
    {
        Mail::to(User::find($this->record->customer_user_id))->send(new TicketCreated());
        $ticket = $this->record;
        $ticketHistory = new TicketHistory([
            'title' => 'ticket has been created',
            'body' => 'ticket has been created',
            'work_order' => null,
            'sub_work_order' => null,
            'created_at' => now(),
        ]);
        $ticket->ticketHistory()->save($ticketHistory);
        $ticket->save();
    }
}
