<?php

namespace App\Filament\Widgets;

use App\Models\Ticket;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UserStatsOverview extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 1;

    protected static ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $tickets = auth()->user()->customerTickets->merge(auth()->user()->technicalSupportTickets);
        $tickets->merge(auth()->user()->highTechnicalSupportTickets);
        return [
            Stat::make(
                'Ticket Count',
                $tickets->count()
            ),
        ];
    }

    public static function canView(): bool
    {
        return auth()->user()->can('widget_UserStatsOverview');
    }
}
