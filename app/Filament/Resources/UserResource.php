<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\Country;
use App\Models\Department;
use App\Models\User;
use Exception;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Password Confirmation')
                    ->schema([
                        Forms\Components\TextInput::make('password_confirmation')
                            ->columnSpan('full')
                            ->dehydrated(false)
                            ->label('Your password')
                            ->required()
                            ->password()
                            ->currentPassword(),
                    ]),
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->maxLength(64),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->disabled(fn (Page $livewire) => $livewire instanceof EditUser)
                            ->dehydrated(fn (Page $livewire) => $livewire instanceof EditUser)
                            ->maxLength(128),
                        Forms\Components\TextInput::make('password')
                            ->type('password')
                            ->maxLength(255)
                            ->required(fn (Page $livewire) => $livewire instanceof CreateUser)
                            ->afterStateHydrated(function ($component) {
                                $component->state(null);
                            })
                            ->dehydrateStateUsing(static function ($state) {
                                return filled($state) ? Hash::make($state) : $state;
                            })
                            ->dehydrated(static function ($state) {
                                return filled($state);
                            })
                            ->label(fn (Page $livewire) => $livewire instanceof CreateUser ? 'Password' : 'New Password'),
                    ])
                    ->columns(3),
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\DatePicker::make('email_verified_at')
                            ->dehydrated(false)
                            ->disabled(),
                        Forms\Components\DatePicker::make('blocked_at')
                            ->dehydrated(false)
                            ->disabled(),
                        Forms\Components\DatePicker::make('approved_at')
                            ->dehydrated(false)
                            ->disabled(),
                    ])
                    ->columns(3),
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Select::make('country_id')
                            ->required()
                            ->label('Country')
                            ->options(Country::all()->pluck('country_name', 'id')),
                        Forms\Components\Select::make('department_id')
                            ->required()
                            ->label('Department')
                            ->options(Department::all()->pluck('title', 'id')),
                        Forms\Components\CheckboxList::make('roles')
                            ->relationship('roles', 'name'),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('department_id'),
                Tables\Columns\TextColumn::make('email'),
                Tables\Columns\TextColumn::make('email_verified_at'),
                Tables\Columns\TextColumn::make('blocked_at'),
                Tables\Columns\TextColumn::make('approved_at'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('block')
                    ->modalHeading('Confirm password to continue')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\TextInput::make('password')
                            ->label('Your password')
                            ->required()
                            ->password()
                            ->currentPassword(),
                    ])
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(function (User $record) {
                        return $record->blocked_at === null;
                        // TODO handle admins
                    })
                    ->action(function (User $record) {
                        try {
                            $record->blocked_at = now();
                            $record->save();
                            Notification::make()
                                ->title('User blocked')
                                ->success()
                                ->send();
                        } catch (Exception $e) {
                            Notification::make()
                                ->title('Error blocking user')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Tables\Actions\Action::make('unblock')
                    ->modalHeading('Confirm password to continue')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\TextInput::make('password')
                            ->label('Your password')
                            ->required()
                            ->password()
                            ->currentPassword(),
                    ])
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(function (User $record) {
                        return $record->blocked_at !== null;
                        // TODO handle admins
                    })
                    ->action(function (User $record) {
                        try {
                            $record->blocked_at = null;
                            $record->save();
                            Notification::make()
                                ->title('User unblocked')
                                ->success()
                                ->send();
                        } catch (Exception $e) {
                            Notification::make()
                                ->title('Error unblocking user')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Tables\Actions\Action::make('approve')
                    ->modalHeading('Confirm password to continue')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\TextInput::make('password')
                            ->label('Your password')
                            ->required()
                            ->password()
                            ->currentPassword(),
                    ])
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(function (User $record) {
                        return $record->approved_at === null;
                        // TODO handle admins
                    })
                    ->action(function (User $record) {
                        try {
                            $record->approved_at = now();
                            $record->save();
                            Notification::make()
                                ->title('User approved')
                                ->success()
                                ->send();
                        } catch (Exception $e) {
                            Notification::make()
                                ->title('Error approving user')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Tables\Actions\Action::make('unapprove')
                    ->modalHeading('Confirm password to continue')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\TextInput::make('password')
                            ->label('Your password')
                            ->required()
                            ->password()
                            ->currentPassword(),
                    ])
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(function (User $record) {
                        return $record->approved_at !== null;
                        // TODO handle admins
                    })
                    ->action(function (User $record) {
                        try {
                            $record->aprrove_at = null;
                            $record->save();
                            Notification::make()
                                ->title('User unapproved')
                                ->success()
                                ->send();
                        } catch (Exception $e) {
                            Notification::make()
                                ->title('Error unapproving user')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
