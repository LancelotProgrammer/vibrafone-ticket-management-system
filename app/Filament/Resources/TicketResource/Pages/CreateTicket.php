<?php

namespace App\Filament\Resources\TicketResource\Pages;

use App\Enums\TicketHandler;
use App\Enums\TicketStatus;
use App\Filament\Resources\TicketResource;
use App\Mail\TicketCreated;
use App\Models\Department;
use App\Models\Level;
use App\Models\Priority;
use App\Models\Ticket;
use App\Models\TicketHistory;
use App\Models\User;
use Carbon\Carbon;
use Error;
use Exception;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use Throwable;

class CreateTicket extends CreateRecord
{
    protected static string $resource = TicketResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $latestTicketIdentifier = Ticket::where('department_id', $data['department_id'])->latest()->value('ticket_identifier');
        $ticketIdentifier = 1;
        if ($latestTicketIdentifier !== null) {
            $parts = explode('-', $latestTicketIdentifier);
            $ticketIdentifier = intval($parts[1]) + 1;
        }
        $ticketIdentifier = Department::where('id', $data['department_id'])->first()->code . '-' . str_pad($ticketIdentifier, 6, '0', STR_PAD_LEFT);

        $data['status'] = TicketStatus::IN_PROGRESS->value;
        $data['handler'] = TicketHandler::TECHNICAL_SUPPORT->value;
        $data['level_id'] = Level::where('code', 1)->first()->id;
        $data['ticket_identifier'] = $ticketIdentifier;

        $priority = Priority::where('id', $data['priority_id'])->first();
        if ($priority->type_id != $data['type_id']) {
            Notification::make()
                ->title('Priority is not a choose of the selected type')
                ->warning()
                ->send();
            $this->halt();
        }

        $created = static::getModel()::create($data);

        $created->customer()->attach(auth()->user()->id, ['owner' => 1]);

        $created->ticketHistory()->save(new TicketHistory([
            'title' => 'Ticket has been created',
            'owner' => auth()->user()->email,
            'work_order' => null,
            'sub_work_order' => null,
            'attachments' => $data['attachments'],
            'status' => TicketStatus::IN_PROGRESS->value,
            'handler' => TicketHandler::TECHNICAL_SUPPORT->value,
            'created_at' => Carbon::now()->toDateTimeString(),
        ]));

        $managersEmails = User::whereHas('roles', function ($query) {
            $query->where('name', 'manager');
        })->pluck('email')->toArray();
        $title = 'Initial Response on Case:' . ' Case ' . ' # ' . $created->ticket_identifier . ' - ' . $created->title;
        try {
            Mail::to($created->customer)->send(new TicketCreated($title, $managersEmails));
        } catch (Exception | Throwable | Error $e) {
            if (App::environment('local')) {
                Notification::make()
                    ->title('Email not sent')
                    ->body($e->getMessage())
                    ->warning()
                    ->send();
            } else {
                Notification::make()
                    ->title('Email not sent')
                    ->warning()
                    ->send();
            }
        }

        return $created;
    }
}
