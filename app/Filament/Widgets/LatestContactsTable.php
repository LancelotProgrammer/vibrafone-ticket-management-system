<?php

namespace App\Filament\Widgets;

use App\Models\Contact;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestContactsTable extends BaseWidget
{
    protected int | string | array $columnSpan = 'half';

    protected static ?int $sort = 7;

    protected static ?string $pollingInterval = null;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Contact::query()->where('read_at', null)->orderBy('created_at', 'DESC')
            )
            ->paginated([5])
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('email'),
                Tables\Columns\TextColumn::make('subject'),
            ]);
    }

    public static function canView(): bool
    {
        return auth()->user()->can('widget_LatestContactsTable');
    }
}
