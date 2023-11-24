<?php

namespace App\Filament\Widgets;

use App\Enums\TicketStatus;
use App\Models\Contact;
use App\Models\Ticket;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $stat = [];
        if (auth()->user()->can('view_archived_count_ticket')) {
            $stat[] = Stat::make('Archived Tickets', Ticket::whereNotNull('deleted_at')->count());
        }
        if (auth()->user()->can('view_canceled_count_ticket')) {
            $stat[] = Stat::make('Canceled Tickets', Ticket::whereNotNull('canceled_at')->count());
        }
        if (auth()->user()->can('view_closed_count_ticket')) {
            $stat[] = Stat::make('Closed Tickets', Ticket::where('status', TicketStatus::CLOSED->value)->count());
        }
        if (auth()->user()->can('view_owned_count_ticket')) {
            $stat[] = Stat::make('Your Owned Tickets', auth()->user()->customerTickets()->where('owner', true)->count());
        }
        if (auth()->user()->can('view_escalated_count_ticket')) {
            $stat[] = Stat::make('Escalated Tickets', Ticket::where('level_id', 3)->count());
        }
        if (auth()->user()->can('view_opened_count_ticket')) {
            $stat[] = Stat::make('Opened Tickets', Ticket::whereNotNull('start_at')->count());
        }
        if (auth()->user()->can('view_total_count_ticket')) {
            $stat[] = Stat::make('Total Tickets', Ticket::count());
        }
        if (auth()->user()->can('view_any_contact')) {
            $stat[] = Stat::make('Total Contacts', Contact::count());
        }
        if (auth()->user()->can('view_any_user')) {
            $stat[] = Stat::make('Total Users', User::count());
        }
        return $stat;
    }

    public static function canView(): bool
    {
        return auth()->user()->can('widget_StatsOverview');
    }
}
