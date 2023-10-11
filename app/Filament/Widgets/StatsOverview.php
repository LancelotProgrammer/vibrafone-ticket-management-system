<?php

namespace App\Filament\Widgets;

use App\Models\Contact;
use App\Models\Ticket;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 1;

    protected static ?string $pollingInterval = null;

    protected function getStats(): array
    {
        return [
            Stat::make('Ticket Count', Ticket::count()),
            Stat::make('Contact Count', Contact::count()),
            Stat::make('User Count', User::count()),
        ];
    }
}
