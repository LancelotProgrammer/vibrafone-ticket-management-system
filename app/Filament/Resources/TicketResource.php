<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TicketResource\Pages;
use App\Filament\Resources\TicketResource\RelationManagers;
use App\Models\Ticket;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TicketResource extends Resource
{
    protected static ?string $model = Ticket::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('type_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('priority_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('department_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('category_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('customer_user_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('technical_support_user_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('high_technical_support_user_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('ticket_identifier')
                    ->required()
                    ->maxLength(64),
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(64),
                Forms\Components\TextInput::make('description')
                    ->maxLength(512),
                Forms\Components\TextInput::make('ne_product')
                    ->required()
                    ->maxLength(64),
                Forms\Components\TextInput::make('sw_version')
                    ->required()
                    ->maxLength(64),
                Forms\Components\TextInput::make('work_order')
                    ->numeric(),
                Forms\Components\TextInput::make('sub_work_order')
                    ->numeric(),
                Forms\Components\TextInput::make('status')
                    ->numeric(),
                Forms\Components\TextInput::make('handler')
                    ->numeric(),
                Forms\Components\DateTimePicker::make('start_at'),
                Forms\Components\DateTimePicker::make('end_at'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('priority_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('department_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer_user_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('technical_support_user_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('high_technical_support_user_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ticket_identifier')
                    ->searchable(),
                Tables\Columns\TextColumn::make('title')
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->searchable(),
                Tables\Columns\TextColumn::make('ne_product')
                    ->searchable(),
                Tables\Columns\TextColumn::make('sw_version')
                    ->searchable(),
                Tables\Columns\TextColumn::make('work_order')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sub_work_order')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('handler')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ]);
    }
    
    public static function getRelations(): array
    {
        return [
            //
        ];
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTickets::route('/'),
            'create' => Pages\CreateTicket::route('/create'),
            'edit' => Pages\EditTicket::route('/{record}/edit'),
        ];
    }    
}
