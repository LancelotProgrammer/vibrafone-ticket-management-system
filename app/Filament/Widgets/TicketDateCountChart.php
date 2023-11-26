<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class TicketDateCountChart extends ChartWidget
{
    protected int | string | array $columnSpan = 'half';

    protected static ?int $sort = 2;

    protected static ?string $pollingInterval = null;

    protected static ?string $maxHeight = '300px';

    protected static ?string $heading = 'Ticket Date Chart';

    public ?string $filter = 'all';

    protected function getFilters(): ?array
    {
        return [
            'all' => 'All',
            'today' => 'Today',
            'week' => 'Last week',
            'month' => 'Last month',
        ];
    }

    protected function getData(): array
    {
        $activeFilter = $this->filter;
        if ($activeFilter === 'today') {
            $tickets = DB::table('tickets')
                ->select(DB::raw('DATE(tickets.created_at) as created_at'), DB::raw('count(*) as count'))
                ->whereDate('tickets.created_at', today())
                ->groupBy(DB::raw('DATE(tickets.created_at)'))
                ->orderBy('created_at', 'desc')
                ->get()
                ->toArray();
            return [
                'datasets' => [
                    [
                        'label' => 'Ticket Date Chart',
                        'data' => array_column($tickets, 'count'),
                        'backgroundColor' => [
                            'rgb(54, 162, 235)',
                        ],
                    ],
                ],
                'labels' => array_column($tickets, 'created_at'),
            ];
        }
        if ($activeFilter === 'week') {
            $tickets = DB::table('tickets')
                ->select(DB::raw('DATE(tickets.created_at) as created_at'), DB::raw('count(*) as count'))
                ->whereBetween('tickets.created_at', [now()->startOfWeek(), now()->endOfWeek()])
                ->groupBy(DB::raw('DATE(tickets.created_at)'))
                ->orderBy('created_at', 'desc')
                ->get()
                ->toArray();
            return [
                'datasets' => [
                    [
                        'label' => 'Ticket Date Chart',
                        'data' => array_column($tickets, 'count'),
                        'backgroundColor' => [
                            'rgb(54, 162, 235)',
                        ],
                    ],
                ],
                'labels' => array_column($tickets, 'created_at'),
            ];
        }
        if ($activeFilter === 'month') {
            $tickets = DB::table('tickets')
                ->select(DB::raw('DATE(tickets.created_at) as created_at'), DB::raw('count(*) as count'))
                ->whereBetween('tickets.created_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->groupBy(DB::raw('DATE(tickets.created_at)'))
                ->orderBy('created_at', 'desc')
                ->get()
                ->toArray();
            return [
                'datasets' => [
                    [
                        'label' => 'Ticket Date Chart',
                        'data' => array_column($tickets, 'count'),
                        'backgroundColor' => [
                            'rgb(54, 162, 235)',
                        ],
                    ],
                ],
                'labels' => array_column($tickets, 'created_at'),
            ];
        }
        if ($activeFilter === 'all') {
            $tickets = DB::table('tickets')
                ->select(DB::raw('DATE(tickets.created_at) as created_at'), DB::raw('count(*) as count'))
                ->groupBy(DB::raw('DATE(tickets.created_at)'))
                ->orderBy('created_at', 'desc')
                ->get()
                ->toArray();
            return [
                'datasets' => [
                    [
                        'label' => 'Ticket Date Chart',
                        'data' => array_column($tickets, 'count'),
                        'backgroundColor' => [
                            'rgb(54, 162, 235)',
                        ],
                    ],
                ],
                'labels' => array_column($tickets, 'created_at'),
            ];
        }
    }

    protected function getType(): string
    {
        return 'bar';
    }

    public static function canView(): bool
    {
        return auth()->user()->can('widget_TicketDateCountChart');
    }
}
