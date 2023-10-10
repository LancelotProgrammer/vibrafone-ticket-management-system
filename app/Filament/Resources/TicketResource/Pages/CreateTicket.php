<?php

namespace App\Filament\Resources\TicketResource\Pages;

use App\Enums\TicketStatus;
use App\Filament\Resources\TicketResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTicket extends CreateRecord
{
    protected static string $resource = TicketResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['customer_user_id'] = auth()->id();
        $data['status'] = TicketStatus::IN_PROGRESS->value;
        return $data;
    }
}
