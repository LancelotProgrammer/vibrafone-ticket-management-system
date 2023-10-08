<?php

namespace App\Filament\Resources\FrequentlyAskedQuestionResource\Pages;

use App\Filament\Resources\FrequentlyAskedQuestionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFrequentlyAskedQuestions extends ListRecords
{
    protected static string $resource = FrequentlyAskedQuestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
