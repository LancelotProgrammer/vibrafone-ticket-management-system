<?php

namespace App\Filament\Resources\TicketResource\Pages;

use App\Filament\Resources\TicketResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class ListTickets extends ListRecords
{
    protected static string $resource = TicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            ExportAction::make()
                ->hidden(!(auth()->user()->can('export_ticket')))
                ->exports([
                    ExcelExport::make()
                        ->fromTable()
                        ->modifyQueryUsing(function ($query) {
                            return $query
                                ->with([
                                    'customer',
                                    'technicalSupport',
                                    'highTechnicalSupport',
                                    'ticketHistory',
                                ]);
                        })
                ]),
        ];
    }
}
