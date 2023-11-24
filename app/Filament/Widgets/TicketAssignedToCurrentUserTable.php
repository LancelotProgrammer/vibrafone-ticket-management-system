<?php

namespace App\Filament\Widgets;

use App\Models\Ticket;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\DB;

class TicketAssignedToCurrentUserTable extends BaseWidget
{
    protected int | string | array $columnSpan = 'half';

    protected static ?int $sort = 2;

    protected static ?string $pollingInterval = null;

    public function table(Table $table): Table
    {
        $user = auth()->user();
        $ticketsIds = [];
        $ticketCustomer = DB::table('ticket_customer')
            ->where('user_id', '=', $user->id)
            ->pluck('ticket_id');
        $ticketsIds = array_merge($ticketsIds, $ticketCustomer->toArray());
        $ticketTechnicalSupport = DB::table('ticket_technical_support')
            ->where('user_id', '=', $user->id)
            ->pluck('ticket_id');
        $ticketsIds = array_merge($ticketsIds, $ticketTechnicalSupport->toArray());
        $ticketHighTechnicalSupport =  DB::table('ticket_high_technical_support')
            ->where('user_id', '=', $user->id)
            ->pluck('ticket_id');
        $ticketsIds = array_merge($ticketsIds, $ticketHighTechnicalSupport->toArray());
        $ticketsIds = array_unique($ticketsIds);
        return $table
            ->query(
                Ticket::query()->whereIn('id', $ticketsIds)->orderBy('created_at', 'DESC')
            )
            ->columns([
                Tables\Columns\TextColumn::make('title')->limit(50),
                Tables\Columns\TextColumn::make('created_at'),
            ]);
    }

    public static function canView(): bool
    {
        return auth()->user()->can('widget_LatestContactsTable');
    }
}
