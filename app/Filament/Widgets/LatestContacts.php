<?php

namespace App\Filament\Widgets;

use App\Models\Contact;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestContacts extends BaseWidget
{
    protected int | string | array $columnSpan = 'half';

    protected static ?int $sort = 2;

    protected static ?string $pollingInterval = null;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Contact::query()->where('read_at', null)
            )
            ->columns([
                // ...
            ]);
    }
}
