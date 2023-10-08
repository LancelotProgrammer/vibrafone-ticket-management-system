<?php

namespace App\Filament\Resources\FrequentlyAskedQuestionGroupResource\Pages;

use App\Filament\Resources\FrequentlyAskedQuestionGroupResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFrequentlyAskedQuestionGroup extends EditRecord
{
    protected static string $resource = FrequentlyAskedQuestionGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
