<?php

namespace App\Filament\Resources\TicketResource\Pages;

use App\Enums\EmailType;
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

            $title = 'Initial Response on Case:' . ' Case ' . ' # ' . $created->ticket_identifier . ' - ' . $created->title;
            Mail::to($created->customer)->queue(new TicketCreated(EmailType::CUSTOMER, $title));
            foreach (User::whereHas('roles', function ($query) {
                $query->where('name', 'super_admin')->orWhere('name', 'manager');
            })->get() as $recipient) {
                Mail::to($recipient)->queue(new TicketCreated(EmailType::ADMIN, $title));
            }
            foreach (User::where('department_id', $created->department_id)->where('level_id', 2)->get() as $recipient) {
                Mail::to($recipient)->queue(new TicketCreated(EmailType::TECHNICAL_SUPPORT, $title));
            }

            return $created;
        });
    }
}
