<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CountryResource\Pages;
use App\Filament\Resources\CountryResource\RelationManagers;
use App\Models\Country;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CountryResource extends Resource
{
    protected static ?string $model = Country::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('country_name')
                            ->required()
                            ->columnSpanFull()
                            ->maxLength(64),
                        Forms\Components\TextInput::make('ar_country_name')
                            ->required()
                            ->maxLength(64),
                        Forms\Components\TextInput::make('en_country_name')
                            ->required()
                            ->maxLength(64),
                        Forms\Components\TextInput::make('country_code')
                            ->required()
                            ->maxLength(8),
                        Forms\Components\TextInput::make('dial_code')
                            ->required()
                            ->maxLength(8),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('country_name'),
                Tables\Columns\TextColumn::make('ar_country_name'),
                Tables\Columns\TextColumn::make('en_country_name'),
                Tables\Columns\TextColumn::make('country_code'),
                Tables\Columns\TextColumn::make('dial_code'),
            ])
            ->filters([
                //
            ])
            ->actions([
                //
            ])
            ->bulkActions([
                //
            ])
            ->emptyStateActions([
                //
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
            'index' => Pages\ListCountries::route('/'),
            'create' => Pages\CreateCountry::route('/create'),
            'edit' => Pages\EditCountry::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }
}
