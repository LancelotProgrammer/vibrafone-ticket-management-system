<?php

namespace App\Filament\Resources\DepartmentResource\Pages;

use App\Filament\Resources\DepartmentResource;
use App\Models\Ticket;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditDepartment extends EditRecord
{
    protected static string $resource = DepartmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if ($record->title === $data['title'] || $record->code === $data['code']) {
            $record->update($data);
            return $record;
        }
        Ticket::where('department_id', $record->id)->chunk(10, function ($tickets) use ($record, $data) {
            foreach ($tickets as $ticket) {
                $newTicketIdentifier = preg_replace("/" . preg_quote($record->code, '/') . "/", $data['code'], $ticket->ticket_identifier, 1);
                $ticket->update([
                    'ticket_identifier' => $newTicketIdentifier,
                ]);
                $ticket->save();
                $ticket->ticketHistory()->whereNotNull('work_order')->chunk(20, function ($ticketHistories) use ($record, $data) {
                    foreach ($ticketHistories as $ticketHistory) {
                        $newTiltle = preg_replace("/" . preg_quote($record->code, '/') . "/", $data['title'], $ticketHistory->title, 1);
                        $ticketHistory->update([
                            'title' => $newTiltle,
                        ]);
                    }
                });
            }
        });
        $record->update($data);
        return $record;
    }
}
