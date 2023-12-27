<?php

namespace App\Filament\Widgets;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use Filament\Widgets\ChartWidget;

class TicketTypeCountChart extends ChartWidget
{
    protected int | string | array $columnSpan = 'half';

    protected static ?int $sort = 1;

    protected static ?string $pollingInterval = null;

    protected static ?string $maxHeight = '300px';

    protected static ?string $heading = 'Ticket Chart';

    protected function getData(): array
    {
        $archivedTickets = Ticket::whereNotNull('deleted_at')->count();
        $canceledTickets = Ticket::whereNotNull('canceled_at')->count();
        $closedTickets = Ticket::where('status', TicketStatus::CLOSED->value)->count();
        $escalatedToHighTechnicalSupport = Ticket::whereNotNull('escalated_to_high_technical_support_at')->count();
        $escalatedToExternalTechnicalSupport = Ticket::whereNotNull('escalated_to_external_technical_support_at')->count();
        $openedTickets = Ticket::whereNotNull('start_at')->count();
        return [
            'datasets' => [
                [
                    'label' => 'Ticket Chart',
                    'data' => [
                        $openedTickets,
                        $closedTickets,
                        $archivedTickets,
                        $canceledTickets,
                        $escalatedToHighTechnicalSupport,
                        $escalatedToExternalTechnicalSupport,
                    ],
                    'backgroundColor' => [
                        'rgb(54, 162, 235)',
                        'rgb(75, 192, 192)',
                        'rgb(255, 205, 86)',
                        'rgb(255, 99, 132)',
                        'rgb(153, 102, 255)',
                        'rgb(45, 102, 255)',
                    ],
                ],
            ],
            'labels' => [
                'Opened Tickets',
                'Closed Tickets',
                'Archived Tickets',
                'Canceled Tickets',
                'Escalated To SL2',
                'Escalated To SL3',
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    public static function canView(): bool
    {
        return auth()->user()->can('widget_TicketTypeCountChart');
    }
}
