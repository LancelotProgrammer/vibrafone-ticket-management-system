<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\Country;
use App\Models\Department;
use App\Models\Level;
use App\Models\User;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
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

class UserResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'User Managment';

    public static function getPermissionPrefixes(): array
    {
        return [
            'view',
            'view_any',
            'create',
            'update',
            'delete',
            'delete_any',
            'block',
            'unblock',
        ];
    }

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
                            ->dehydrated(true)
                            ->maxLength(128),
                        Forms\Components\TextInput::make('company')
                            ->required()
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
                    ->columns(4),
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\DatePicker::make('email_verified_at')
                            ->dehydrated(false)
                            ->disabled(),
                        Forms\Components\DatePicker::make('blocked_at')
                            ->dehydrated(false)
                            ->disabled(),
                    ])
                    ->columns(2),
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
                        Forms\Components\Select::make('level_id')
                            ->required()
                            ->label('Level')
                            ->options(Level::all()->pluck('title', 'id')),
                        Forms\Components\CheckboxList::make('roles')
                            ->relationship('roles', 'name'),
                    ])
                    ->columns(4),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('department.title'),
                Tables\Columns\TextColumn::make('level.title'),
                Tables\Columns\TextColumn::make('email'),
                Tables\Columns\TextColumn::make('roles.name'),
                Tables\Columns\TextColumn::make('blocked_at'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('block')
                    ->hidden(!(auth()->user()->can('block_user')))
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
                        if ($record->hasRole(['manager', 'super_admin'])) {
                            return false;
                        }
                        return $record->blocked_at === null;
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
                    ->hidden(!(auth()->user()->can('unblock_user')))
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
                        if ($record->hasRole(['manager', 'super_admin'])) {
                            return false;
                        }
                        return $record->blocked_at !== null;
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
