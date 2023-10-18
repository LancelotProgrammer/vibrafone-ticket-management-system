<?php

namespace App\Filament\Resources\TicketResource\Pages;

use App\Filament\Resources\TicketResource;
use App\Models\Category;
use App\Models\Department;
use App\Models\Priority;
use App\Models\Type;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;
use pxlrbt\FilamentExcel\Columns\Column;
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
                        ->withColumns([
                            Column::make('type')
                                ->formatStateUsing(function ($record) {
                                    return Type::where('id', $record->type_id)->first()->title;
                                }),
                            Column::make('department')
                                ->formatStateUsing(function ($record) {
                                    return Department::where('id', $record->department_id)->first()->title;
                                }),
                            Column::make('priority')
                                ->formatStateUsing(function ($record) {
                                    return Priority::where('id', $record->priority_id)->first()->title;
                                }),
                            Column::make('category')
                                ->formatStateUsing(function ($record) {
                                    return Category::where('id', $record->category_id)->first()->title;
                                }),
                            Column::make('esclated')
                                ->getStateUsing(function ($record) {
                                    return 'test';
                                }),
                        ]),
                ]),
        ];
    }
}
