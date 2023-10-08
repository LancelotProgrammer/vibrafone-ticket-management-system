<?php

namespace App\Filament\Resources\FrequentlyAskedQuestionGroupResource\Pages;

use App\Filament\Resources\FrequentlyAskedQuestionGroupResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFrequentlyAskedQuestionGroups extends ListRecords
{
    protected static string $resource = FrequentlyAskedQuestionGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
