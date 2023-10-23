<?php

namespace App\Filament\Resources\TicketResource\Pages;

use App\Enums\TicketHandler;
use App\Enums\TicketStatus;
use App\Filament\Resources\TicketResource;
use App\Mail\TicketCreated;
use App\Models\Level;
use App\Models\TicketHistory;
use App\Models\User;
use Carbon\Carbon;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class CreateTicket extends CreateRecord
{
    protected static string $resource = TicketResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {

            $data['status'] = TicketStatus::IN_PROGRESS->value;
            $data['handler'] = TicketHandler::TECHNICAL_SUPPORT->value;
            $data['level_id'] = Level::where('code', 1)->first()->id;

            $created = static::getModel()::create($data);

            $created->customer()->attach(auth()->user()->id);

            $ticketHistory = new TicketHistory([
                'title' => 'ticket has been created',
                'body' => 'ticket has been created',
                'owner' => auth()->user()->email,
                'work_order' => null,
                'sub_work_order' => null,
                'attachments' => $data['attachments'],
                'status' => TicketStatus::IN_PROGRESS->value,
                'handler' => TicketHandler::TECHNICAL_SUPPORT->value,
                'created_at' => Carbon::now()->toDateTimeString(),
            ]);
            $created->ticketHistory()->save($ticketHistory);
            $created->save();

            $managersEmails = User::whereHas('roles', function ($query) {
                $query->where('name', 'manager');
            })->pluck('email')->toArray();
            $title = 'Initial Response on Case:' . ' Case ' . ' # ' . $created->ticket_identifier . ' - ' . $created->title;
            Mail::to($created->customer)->send(new TicketCreated($title, $managersEmails));

            return $created;
        });
    }
}
