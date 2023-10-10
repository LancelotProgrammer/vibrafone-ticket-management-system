<?php

namespace App\Filament\Resources\TicketResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TicketHistoryRelationManager extends RelationManager
{
    protected static string $relationship = 'ticketHistory';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DateTimePicker::make('created_at')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $query->orderByDesc('created_at', 'des');
            })
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('title')->limit(50),
                Tables\Columns\TextColumn::make('work_order'),
                Tables\Columns\TextColumn::make('sub_work_order'),
                Tables\Columns\TextColumn::make('created_at'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                //
            ])
            ->emptyStateActions([
                //
            ]);
    }
}
