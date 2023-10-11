<?php

namespace App\Filament\Resources\TicketResource\Pages;

use App\Enums\EmailType;
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
        $this->record->customer()->attach(auth()->user()->id);
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
        $this->record->ticketHistory()->save($ticketHistory);
        $this->record->save();
        $title = 'Initial Response on Case:' . ' Case ' . ' # ' . $this->record->ticket_identifier . ' - ' . $this->record->title;
        Mail::to($this->record->customer)->send(new TicketCreated(EmailType::CUSTOMER, $title));
        foreach (User::whereHas('roles', function ($query) {
            $query->where('name', 'super_admin')->orWhere('name', 'manager');
        })->get() as $recipient) {
            Mail::to($recipient)->send(new TicketCreated(EmailType::ADMIN, $title));
        }
        foreach (User::where('department_id', $this->record->department_id)->where('level_id', 2)->get() as $recipient) {
            Mail::to($recipient)->send(new TicketCreated(EmailType::TECHNICAL_SUPPORT, $title));
        }
    }
}
