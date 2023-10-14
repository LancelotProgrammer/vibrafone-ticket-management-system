<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class TicketChart extends ChartWidget
{
    protected int | string | array $columnSpan = 'half';

    protected static ?int $sort = 3;

    protected static ?string $pollingInterval = null;

    protected static ?string $heading = 'Ticket Chart';

    protected function getData(): array
    {
        $tickets = DB::table('tickets')
            ->select(DB::raw('DATE(tickets.created_at) as created_at'), DB::raw('count(*) as count'))
            ->groupBy(DB::raw('DATE(tickets.created_at)'))
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
        return [
            'datasets' => [
                [
                    'label' => 'Ticket Chart',
                    'data' => array_column($tickets, 'count'),
                    'backgroundColor' => [
                        'rgb(54, 162, 235)',
                    ],
                ],
            ],
            'labels' => array_column($tickets, 'created_at'),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    public static function canView(): bool
    {
        return auth()->user()->can('widget_TicketChart');
    }
}
