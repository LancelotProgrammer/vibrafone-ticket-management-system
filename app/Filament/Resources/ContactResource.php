<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContactResource\Pages;
use App\Filament\Resources\ContactResource\RelationManagers;
use App\Mail\ReplyToContact;
use App\Models\Contact;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Exception;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Mail;

class ContactResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Contact::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox-stack';

    protected static ?string $navigationGroup = 'Resources';

    public static function getPermissionPrefixes(): array
    {
        return [
            'view',
            'view_any',
            'create',
            'update',
            'delete',
            'delete_any',
            'mark_as_read',
            'mark_as_important',
            'reply',
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereNull('read_at')->count() > 0 ? static::getModel()::whereNull('read_at')->count() : null;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Fieldset::make('Contact Details')
                    ->schema([
                        Forms\Components\TextInput::make('name'),
                        Forms\Components\TextInput::make('email'),
                        Forms\Components\TextInput::make('subject'),
                    ])
                    ->columns(3),
                Forms\Components\Fieldset::make('Contact')
                    ->schema([
                        Forms\Components\Textarea::make('description'),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('email')->searchable(),
                Tables\Columns\TextColumn::make('subject'),
                Tables\Columns\TextColumn::make('created_at'),
                Tables\Columns\IconColumn::make('is_important')
                    ->color(fn ($state) => match ($state) {
                        0 => 'danger',
                        1 => 'success',
                    })
                    ->icon(fn ($state) => match ($state) {
                        0 => 'heroicon-o-x-circle',
                        1 => 'heroicon-o-check-circle',
                    }),
            ])
            ->filters([
                TernaryFilter::make('read_at')
                    ->placeholder('All records')
                    ->trueLabel('Yes')
                    ->falseLabel('No')
                    ->default(false)
                    ->queries(
                        true: fn (Builder $query) => $query->where('read_at', '!=', null),
                        false: fn (Builder $query) => $query->where('read_at', null),
                        blank: fn (Builder $query) => $query,
                    ),
                TernaryFilter::make('is_important')
                    ->placeholder('All records')
                    ->trueLabel('Only important records')
                    ->falseLabel('Only unimportant records')
                    ->queries(
                        true: fn (Builder $query) => $query->where('is_important', true),
                        false: fn (Builder $query) => $query->where('is_important', false),
                        blank: fn (Builder $query) => $query,
                    ),
                Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from'),
                        Forms\Components\DatePicker::make('created_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('View Contact'),
                ActionGroup::make([
                    Tables\Actions\Action::make('Mark as Read')
                        ->hidden(!auth()->user()->can('mark_as_read_contact'))
                        ->modalHeading('Confirm password to continue')
                        ->requiresConfirmation()
                        ->form([
                            Forms\Components\TextInput::make('password')
                                ->label('Your password')
                                ->required()
                                ->password()
                                ->currentPassword(),
                        ])
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->visible(fn (Contact $record): bool => $record->read_at === null)
                        ->action(function (Contact $record) {
                            $record->read_at = now();
                            $record->save();
                            Notification::make()
                                ->title('Contact Marked as Read')
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\Action::make('Mark as important')
                        ->hidden(!auth()->user()->can('mark_as_important_contact'))
                        ->modalHeading('Confirm password to continue')
                        ->requiresConfirmation()
                        ->form([
                            Forms\Components\TextInput::make('password')
                                ->label('Your password')
                                ->required()
                                ->password()
                                ->currentPassword(),
                        ])
                        ->icon('heroicon-o-exclamation-circle')
                        ->color('success')
                        ->visible(fn (Contact $record): bool => $record->is_important == false)
                        ->action(function (Contact $record) {
                            $record->is_important = true;
                            $record->save();
                            Notification::make()
                                ->title('Contact Marked as important')
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\Action::make('Mark as not important')
                        ->hidden(!auth()->user()->can('mark_as_important_contact'))
                        ->modalHeading('Confirm password to continue')
                        ->requiresConfirmation()
                        ->form([
                            Forms\Components\TextInput::make('password')
                                ->label('Your password')
                                ->required()
                                ->password()
                                ->currentPassword(),
                        ])
                        ->icon('heroicon-o-exclamation-circle')
                        ->color('danger')
                        ->visible(fn (Contact $record): bool => $record->is_important == true)
                        ->action(function (Contact $record) {
                            $record->is_important = false;
                            $record->save();
                            Notification::make()
                                ->title('Contact Marked as not important')
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\DeleteAction::make()
                        ->modalHeading('Confirm password to continue')
                        ->requiresConfirmation()
                        ->form([
                            Forms\Components\TextInput::make('password')
                                ->label('Your password')
                                ->required()
                                ->password()
                                ->currentPassword(),
                        ]),
                    Tables\Actions\Action::make('reply')
                        ->hidden(!auth()->user()->can('reply_contact'))
                        ->color('primary')
                        ->form(function (Contact $record) {
                            return [
                                Forms\Components\TextInput::make('password')
                                    ->label('Your password')
                                    ->required()
                                    ->password()
                                    ->currentPassword(),
                                Forms\Components\TextInput::make('email')
                                    ->default($record->email)
                                    ->required()
                                    ->label('email')
                                    ->placeholder('email'),
                                Forms\Components\Textarea::make('message')
                                    ->required()
                                    ->label('Message')
                                    ->placeholder('Message'),
                            ];
                        })
                        ->requiresConfirmation()
                        ->action(function (array $data, Contact $record) {
                            try {
                                $record->read_at = now();
                                $record->save();
                                Mail::to($data['email'])->send(new ReplyToContact($data['message']));
                                Notification::make()
                                    ->title('User notified')
                                    ->success()
                                    ->send();
                            } catch (Exception $e) {
                                Notification::make()
                                    ->title('Error notifying user')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),
                ])
                ->button()
                ->label('Actions'),
            ])
            ->bulkActions([
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
            'index' => Pages\ListContacts::route('/'),
            'create' => Pages\CreateContact::route('/create'),
            'edit' => Pages\EditContact::route('/{record}/edit'),
        ];
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
