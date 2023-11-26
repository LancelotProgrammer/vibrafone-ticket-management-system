<?php

namespace App\Filament\Widgets;

use App\Models\Ticket;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class TicketLevelCountChart extends ChartWidget
{
    protected int | string | array $columnSpan = 'half';

    protected static ?int $sort = 3;

    protected static ?string $pollingInterval = null;

    protected static ?string $maxHeight = '300px';

    protected static ?string $heading = 'Ticket Level Chart';

    protected function getData(): array
    {
        $tickets = Ticket::select('levels.code', DB::raw('count(*) as ticket_count'))
            ->join('levels', 'tickets.level_id', '=', 'levels.id')
            ->groupBy('levels.code')
            ->get()
            ->toArray();
        $tickets = array_combine(array_column($tickets, 'code'), array_column($tickets, 'ticket_count'));
        return [
            'datasets' => [
                [
                    'label' => 'Ticket Level Chart',
                    'data' => array_values($tickets),
                    'backgroundColor' => [
                        'rgb(54, 162, 235)',
                    ],
                ],
            ],
            'labels' => array_keys($tickets),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    public static function canView(): bool
    {
        return auth()->user()->can('widget_TicketLevelCountChart');
    }
}
