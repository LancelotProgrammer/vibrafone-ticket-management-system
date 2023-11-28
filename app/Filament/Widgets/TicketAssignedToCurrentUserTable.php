<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\TicketResource;
use App\Models\Ticket;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\DB;

class TicketAssignedToCurrentUserTable extends BaseWidget
{
    protected int | string | array $columnSpan = 'half';

    protected static ?int $sort = 6;

    protected static ?string $maxHeight = '300px';

    protected static ?string $pollingInterval = null;

    protected static ?string $heading = 'Ticket Assigned To You';

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
                Tables\Columns\TextColumn::make('title')->limit(20),
                Tables\Columns\TextColumn::make('created_at'),

            ])
            ->paginated([5])
            ->actions([
                Tables\Actions\Action::make('go_to')
                    ->label(function ($record) {
                        if (TicketResource::isTicketEnabled($record)) {
                            if (auth()->user()->can('can_access_any_ticket')) {
                                return 'GO TO';
                            } else {
                                return 'Closed';
                            }
                        } else {
                            return 'Closed';
                        }
                    })
                    ->color(function ($record) {
                        if (TicketResource::isTicketEnabled($record)) {
                            if (auth()->user()->can('can_access_any_ticket')) {
                                return 'success';
                            } else {
                                return 'danger';
                            }
                        } else {
                            return 'danger';
                        }
                    })
                    ->disabled(function ($record) {
                        if (TicketResource::isTicketEnabled($record)) {
                            if (auth()->user()->can('can_access_any_ticket')) {
                                return false;
                            } else {
                                return true;
                            }
                        } else {
                            return true;
                        }
                    })
                    ->action(function ($record) {
                        return redirect(TicketResource::getUrl('edit', ['record' => $record->id]));
                    }),
            ]);
    }

    public static function canView(): bool
    {
        return auth()->user()->can('widget_TicketAssignedToCurrentUserTable');
    }
}
