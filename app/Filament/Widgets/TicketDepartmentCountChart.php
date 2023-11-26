<?php

namespace App\Filament\Widgets;

use App\Models\Ticket;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class TicketDepartmentCountChart extends ChartWidget
{
    protected int | string | array $columnSpan = 'half';

    protected static ?int $sort = 4;

    protected static ?string $pollingInterval = null;

    protected static ?string $maxHeight = '300px';

    protected static ?string $heading = 'Ticket Department Chart';

    protected function getData(): array
    {
        $tickets = Ticket::select('departments.title', DB::raw('count(*) as ticket_count'))
            ->join('departments', 'tickets.department_id', '=', 'departments.id')
            ->groupBy('departments.title')
            ->get()
            ->toArray();
        $tickets = array_combine(array_column($tickets, 'title'), array_column($tickets, 'ticket_count'));
        return [
            'datasets' => [
                [
                    'label' => 'Ticket Department Chart',
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
        return auth()->user()->can('widget_TicketDepartmentCountChart');
    }
}
