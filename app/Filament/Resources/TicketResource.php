<?php

namespace App\Filament\Resources;

use App\Enums\TicketHandler;
use App\Enums\TicketStatus;
use App\Enums\TicketSubWorkOrder;
use App\Enums\TicketWorkOrder;
use App\Filament\Resources\TicketResource\Pages;
use App\Filament\Resources\TicketResource\Pages\CreateTicket;
use App\Filament\Resources\TicketResource\Pages\EditTicket;
use App\Filament\Resources\TicketResource\Pages\ViewTicket;
use App\Filament\Resources\TicketResource\RelationManagers\TicketHistoryRelationManager;
use App\Models\Category;
use App\Models\Department;
use App\Models\Level;
use App\Models\Priority;
use App\Models\Ticket;
use App\Models\TicketHistory;
use App\Models\Type;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Exception;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class TicketResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Ticket::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

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

            'can_view_all',
            'can_edit_all',
            'can_access_all',
            'can_archive',

            'export',

            'view_handler',
            'view_status',
            'view_created_at',
            'view_start_at',
            'view_end_at',
            'view_archived_at',
            'view_customers',
            'view_technical_supports',
            'view_high_technical_supports',
            'view_all_order_type',
            'view_customer_order_type',
            'view_high_technical_support_order_type',

            'can_filter_table',
            'can_not_self_assign',
            'can_select_any_department',

            'add_technical_support',
            'remove_technical_support',
            'add_high_technical_support',
            'remove_high_technical_support',
            'escalate',
            'assign_technical_support',
            'can_be_assigned_as_technical_support',
            'create_work_order_type',
            'view_all_create_order_type',
            'view_customer_create_order_type',
            'view_high_technical_support_create_order_type',
            'send_email_in_order_type',

            'view_history',
            'edit_history_date',
            'view_history_all_order_type',
            'view_history_customer_order_type',
            'view_history_high_technical_support_order_type',
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Create Info')
                    ->visible(function (Page $livewire) {
                        return $livewire instanceof CreateTicket;
                    })
                    ->schema(Self::getCreateForm()),
                Forms\Components\Section::make('Edit Info')
                    ->visible(function (Page $livewire) {
                        return $livewire instanceof EditTicket;
                    })
                    ->schema(Self::getEditForm()),
                Forms\Components\Section::make('View Info')
                    ->visible(function (Page $livewire) {
                        return $livewire instanceof ViewTicket;
                    })
                    ->schema(Self::getViewForm()),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('10s')
            ->modifyQueryUsing(function (Builder $query) {
                // NOTE: here we filter the tickets based on user type / department / level
                // NOTE: ->whereNull('deleted_at') is added to the users that does not have filter permission
                // filter for high support manager
                if (auth()->user()->level_id == 3 && auth()->user()->can('can_view_all_ticket')) {
                    return $query
                        ->where('level_id', auth()->user()->level_id)
                        ->whereNull('deleted_at')
                        ->orderByDesc('id', 'des');
                }
                // filter for manager / admin / dev
                if (auth()->user()->can('can_view_all_ticket')) {
                    return $query
                        ->orderByDesc('id', 'des');
                }
                // filter for high support
                if (auth()->user()->level_id == 3) {
                    return $query
                        ->where('department_id', auth()->user()->department_id)
                        ->where('level_id', auth()->user()->level_id)
                        ->whereNull('deleted_at')
                        ->orderByDesc('id', 'des');
                }
                // filter for support / customer
                if (auth()->user()->level_id == 2 || auth()->user()->level_id == 1) {
                    return $query
                        ->where('department_id', auth()->user()->department_id)
                        ->whereNull('deleted_at')
                        ->orderByDesc('id', 'des');
                }
                return $query
                    ->where('department_id', auth()->user()->department_id)
                    ->whereNull('deleted_at')
                    ->orderByDesc('id', 'des');
            })
            ->columns([
                Split::make([
                    Stack::make([
                        Tables\Columns\TextColumn::make('ticket_identifier')
                            ->icon('heroicon-m-identification'),
                        Tables\Columns\TextColumn::make('title')
                            ->limit(25)
                            ->icon('heroicon-m-bars-3-bottom-left'),
                    ]),
                    Split::make([
                        Stack::make([
                            Tables\Columns\TextColumn::make('department.title')
                                ->icon('heroicon-m-building-office'),
                            Tables\Columns\TextColumn::make('priority.title')
                                ->icon('heroicon-m-exclamation-circle'),
                        ]),
                        Stack::make([
                            Tables\Columns\TextColumn::make('type.title')
                                ->icon('heroicon-m-hashtag'),
                            Tables\Columns\TextColumn::make('category.title')
                                ->icon('heroicon-m-cog-8-tooth'),
                        ]),
                    ]),
                    Stack::make([
                        Tables\Columns\TextColumn::make('customer.email')
                            ->icon('heroicon-m-user-circle')
                            ->hidden(!(auth()->user()->can('view_customers_ticket'))),
                        Tables\Columns\TextColumn::make('technicalSupport.email')
                            ->icon('heroicon-m-user-group')
                            ->hidden(!(auth()->user()->can('view_technical_supports_ticket'))),
                        Tables\Columns\TextColumn::make('highTechnicalSupport.email')
                            ->icon('heroicon-m-user-plus')
                            ->hidden(!(auth()->user()->can('view_high_technical_supports_ticket'))),
                    ]),
                    Stack::make([
                        Tables\Columns\TextColumn::make('status')
                            ->formatStateUsing(function ($state) {
                                return 'Current Status: ' . $state;
                            })
                            ->hidden(!(auth()->user()->can('view_status_ticket'))),
                        Tables\Columns\TextColumn::make('created_at')
                            ->formatStateUsing(function ($state) {
                                return 'Created-at: ' . $state;
                            })
                            ->hidden(!(auth()->user()->can('view_created_at_ticket'))),
                        Tables\Columns\TextColumn::make('start_at')
                            ->formatStateUsing(function ($state) {
                                return 'Started-at: ' . $state;
                            })
                            ->hidden(!(auth()->user()->can('view_start_at_ticket'))),
                        Tables\Columns\TextColumn::make('end_at')
                            ->formatStateUsing(function ($state) {
                                return 'Ended-at: ' . $state;
                            })
                            ->hidden(!(auth()->user()->can('view_end_at_ticket'))),
                        Tables\Columns\TextColumn::make('deleted_at')
                            ->formatStateUsing(function ($state) {
                                return 'Archived-at: ' . $state;
                            })
                            ->hidden(!(auth()->user()->can('view_archived_at_ticket'))),
                    ]),
                ]),
            ])
            ->filters([
                TernaryFilter::make('deleted_at')
                    ->hidden(!(auth()->user()->can('can_filter_table_ticket')))
                    ->label('Is Archived')
                    ->placeholder('Is Archived')
                    ->trueLabel('Yes')
                    ->falseLabel('No')
                    ->default(false)
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('deleted_at'),
                        false: fn (Builder $query) => $query->whereNull('deleted_at'),
                        blank: fn (Builder $query) => $query,
                    ),
                SelectFilter::make('handler')
                    ->hidden(!(auth()->user()->can('can_filter_table_ticket')))
                    ->options([
                        TicketHandler::CUSTOMER->value => 'Customer',
                        TicketHandler::TECHNICAL_SUPPORT->value => 'Technical Support',
                        TicketHandler::HIGH_LEVEL_SUPPORT->value => 'High level support',
                    ]),
                SelectFilter::make('status')
                    ->hidden(!(auth()->user()->can('can_filter_table_ticket')))
                    ->options([
                        TicketStatus::IN_PROGRESS->value => 'In Progress',
                        TicketStatus::CUSTOMER_PENDING->value => 'Customer Pending',
                        TicketStatus::CUSTOMER_UNDER_MONITORING->value => 'Under Monitoring',
                        TicketStatus::CLOSED->value => 'Closed',
                        TicketStatus::HIGHT_LEVEL_SUPPORT_PENDING->value => 'Hight Level Support Pending',
                        TicketStatus::TECHNICAL_SUPPORT_PENDING->value => 'Technical Support Pending',
                        TicketStatus::TECHNICAL_SUPPORT_UNDER_MONITORING->value => 'Under Monitoring',
                    ]),
                Filter::make('created_at')
                    ->hidden(!(auth()->user()->can('can_filter_table_ticket')))
                    ->form([
                        DatePicker::make('created_from'),
                        DatePicker::make('created_until'),
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
                    })
            ])
            ->actions([
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ViewAction::make()
                    ->visible(function ($record) {
                        if (auth()->user()->can('can_view_all_ticket')) {
                            return true;
                        }
                        if (
                            $record->technicalSupport->contains(auth()->user()->id) ||
                            $record->highTechnicalSupport->contains(auth()->user()->id) ||
                            $record->customer->contains(auth()->user()->id)
                        ) {
                            return true;
                        } else {
                            return false;
                        }
                    }),
                Tables\Actions\EditAction::make()
                    ->visible(function ($record) {
                        if ($record->status == TicketStatus::CLOSED->value) {
                            return false;
                        }
                        if (auth()->user()->can('can_access_all_ticket')) {
                            return true;
                        }
                        if (
                            $record->technicalSupport->contains(auth()->user()->id) ||
                            $record->highTechnicalSupport->contains(auth()->user()->id) ||
                            $record->customer->contains(auth()->user()->id)
                        ) {
                            return true;
                        } else {
                            return false;
                        }
                    }),
                Tables\Actions\Action::make('archive')
                    ->hidden(!(auth()->user()->can('can_archive_ticket')))
                    ->requiresConfirmation()
                    ->visible(function (Ticket $record) {
                        return is_null($record->deleted_at) && $record->status == TicketStatus::CLOSED->value;
                    })
                    ->action(function (Ticket $record): void {
                        DB::transaction(function () use ($record) {
                            $record->deleted_at = now();
                            $ticketHistory = new TicketHistory([
                                'ticket_id' => $record->id,
                                'title' => 'Ticket has been archived',
                                'owner' => auth()->user()->email,
                                'work_order' => $record->work_order,
                                'sub_work_order' => $record->sub_work_order,
                                'status' => $record->status,
                                'handler' => $record->handler,
                                'created_at' => now(),
                            ]);
                            $record->ticketHistory()->save($ticketHistory);
                            $record->save();
                        });
                        Notification::make()
                            ->title('Ticket has been archived')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('assign')
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
                    ->visible(function ($record) {
                        if ($record->status == TicketStatus::CLOSED->value) {
                            return false;
                        }
                        if (auth()->user()->can('can_not_self_assign_ticket')) {
                            return false;
                        }
                        if (
                            $record->customer->contains(auth()->user()->id) ||
                            $record->technicalSupport->contains(auth()->user()->id) ||
                            $record->HighTechnicalSupport->contains(auth()->user()->id)
                        ) {
                            return false;
                        } else {
                            return true;
                        }
                    })
                    ->action(function ($record) {
                        try {
                            DB::transaction(function () use ($record) {
                                if (auth()->user()->level_id == 1) {
                                    $record->customer()->attach(auth()->user()->id);
                                }
                                if (auth()->user()->level_id == 2) {
                                    $record->technicalSupport()->attach(auth()->user()->id);
                                }
                                if (auth()->user()->level_id == 3) {
                                    $record->HighTechnicalSupport()->attach(auth()->user()->id);
                                }
                                $ticketHistory = new TicketHistory([
                                    'ticket_id' => $record->id,
                                    'title' => 'Ticket has been assigned to: ' . auth()->user()->email,
                                    'owner' => auth()->user()->email,
                                    'work_order' => $record->work_order,
                                    'sub_work_order' => $record->sub_work_order,
                                    'status' => $record->status,
                                    'handler' => $record->handler,
                                    'created_at' => now(),
                                ]);
                                $record->ticketHistory()->save($ticketHistory);
                                if (is_null($record->start_at)) {
                                    $record->start_at = now();
                                }
                                $record->save();
                            });
                            Notification::make()
                                ->title('ticket assigned to you')
                                ->success()
                                ->send();
                        } catch (Exception $e) {
                            Notification::make()
                                ->title('Error assigning ticket')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                ExportBulkAction::make()
                    ->hidden(!(auth()->user()->can('export_ticket')))
                    ->exports([
                        ExcelExport::make()
                            ->withColumns([
                                Column::make('ticket_identifier'),
                                Column::make('type')
                                    ->formatStateUsing(function ($record) {
                                        return Type::where('id', $record->type_id)->first()->title;
                                    }),
                                Column::make('department')
                                    ->formatStateUsing(function ($record) {
                                        return Department::where('id', $record->department_id)->first()->title;
                                    }),
                                Column::make('priority')
                                    ->formatStateUsing(function ($record) {
                                        return Priority::where('id', $record->priority_id)->first()->title;
                                    }),
                                Column::make('category')
                                    ->formatStateUsing(function ($record) {
                                        return Category::where('id', $record->category_id)->first()->title;
                                    }),
                                Column::make('esclated')
                                    ->getStateUsing(function ($record) {
                                        return 'test';
                                    }),
                            ]),
                    ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            TicketHistoryRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTickets::route('/'),
            'create' => Pages\CreateTicket::route('/create'),
            'edit' => Pages\EditTicket::route('/{record}/edit'),
            'view' => Pages\ViewTicket::route('/{record}'),
        ];
    }

    private static function getCreateForm(): array
    {
        return [
            Forms\Components\Section::make('Ticket Info')
                ->schema([
                    Forms\Components\TextInput::make('ticket_identifier')
                        ->disabled(true)
                        ->dehydrated(true)
                        ->required()
                        ->maxLength(64),
                    Forms\Components\TextInput::make('title')
                        ->required()
                        ->maxLength(64),
                    Forms\Components\TextInput::make('ne_product')
                        ->required()
                        ->maxLength(64),
                    Forms\Components\TextInput::make('sw_version')
                        ->required()
                        ->maxLength(64),
                    Forms\Components\TextInput::make('company')
                        ->default(auth()->user()->company)
                        ->columnSpanFull(),
                    Forms\Components\Textarea::make('description')
                        ->required()
                        ->columnSpanFull()
                        ->maxLength(512),
                    Forms\Components\Section::make('Ticket Meta Data')
                        ->schema([
                            Forms\Components\Select::make('type_id')
                                ->required()
                                ->label('Type')
                                ->options(Type::all()->pluck('title', 'id'))
                                ->live(),
                            Forms\Components\Select::make('priority_id')
                                ->required()
                                ->label('Priority')
                                ->options(function ($get) {
                                    return Priority::where('type_id', $get('type_id'))->pluck('title', 'id');
                                }),
                            Forms\Components\Select::make('department_id')
                                ->required()
                                ->label('Department')
                                ->live()
                                ->afterStateUpdated(function ($state, $set) {
                                    $latestTicketIdentifier = Ticket::where('department_id', $state)->latest()->value('ticket_identifier');
                                    $ticketIdentifier = 1;
                                    if ($latestTicketIdentifier !== null) {
                                        $parts = explode('-', $latestTicketIdentifier);
                                        $ticketIdentifier = intval($parts[1]) + 1;
                                    }
                                    $ticketIdentifier = Department::where('id', $state)->first()->code . '-' . str_pad($ticketIdentifier, 6, '0', STR_PAD_LEFT);
                                    $set(
                                        'ticket_identifier',
                                        $ticketIdentifier
                                    );
                                })
                                ->options(function () {
                                    if (auth()->user()->can('can_select_any_department_ticket')) {
                                        return Department::all()->where('title', '!=', 'default')->pluck('title', 'id');
                                    } else {
                                        return Department::all()->where('title', '!=', 'default')->where('id', auth()->user()->department_id)->pluck('title', 'id');
                                    }
                                }),
                            Forms\Components\Select::make('category_id')
                                ->required()
                                ->label('Technology')
                                ->options(Category::all()->pluck('title', 'id')),
                        ])
                        ->columnSpan(4)
                        ->columns(4),
                    Forms\Components\Section::make('Ticket Files')
                        ->schema([
                            Forms\Components\FileUpload::make('attachments')
                                ->openable()
                                ->columnSpanFull()
                                ->multiple(),
                        ])
                        ->columnSpan(4)
                        ->columns(3),
                ])
                ->columnSpan(3)
                ->columns(4),
        ];
    }

    private static function getEditForm(): array
    {
        return [
            Forms\Components\Section::make('Ticket Info')
                ->schema([
                    Forms\Components\TextInput::make('ticket_identifier')
                        ->disabled(true)
                        ->dehydrated(true),
                    Forms\Components\TextInput::make('title')
                        ->disabled(
                            function ($record) {
                                return self::getEditAllCondition($record);
                            }
                        )
                        ->dehydrated(true)
                        ->required()
                        ->maxLength(64),
                    Forms\Components\TextInput::make('ne_product')
                        ->disabled(
                            function ($record) {
                                return self::getEditAllCondition($record);
                            }
                        )
                        ->dehydrated(true)
                        ->required()
                        ->maxLength(64),
                    Forms\Components\TextInput::make('sw_version')
                        ->disabled(
                            function ($record) {
                                return self::getEditAllCondition($record);
                            }
                        )
                        ->dehydrated(true)
                        ->required()
                        ->maxLength(64),
                    Forms\Components\TextInput::make('company')
                        ->disabled(
                            function ($record) {
                                return self::getEditAllCondition($record);
                            }
                        )
                        ->dehydrated(true)
                        ->columnSpanFull(),
                    Forms\Components\Textarea::make('description')
                        ->disabled(
                            function ($record) {
                                return self::getEditAllCondition($record);
                            }
                        )
                        ->dehydrated(true)
                        ->required()
                        ->columnSpanFull()
                        ->maxLength(512),
                    Forms\Components\Section::make('Ticket Meta Data')
                        ->schema([
                            Forms\Components\Select::make('type_id')
                                ->disabled(true)
                                ->dehydrated(true)
                                ->required()
                                ->label('Type')
                                ->options(Type::all()->pluck('title', 'id')),
                            Forms\Components\Select::make('priority_id')
                                ->disabled(true)
                                ->dehydrated(true)
                                ->required()
                                ->label('Priority')
                                ->options(Priority::all()->pluck('title', 'id')),
                            Forms\Components\Select::make('department_id')
                                ->disabled(true)
                                ->dehydrated(true)
                                ->required()
                                ->label('Department')
                                ->options(Department::all()->pluck('title', 'id')),
                            Forms\Components\Select::make('category_id')
                                ->disabled(true)
                                ->dehydrated(true)
                                ->required()
                                ->label('Technology')
                                ->options(Category::all()->pluck('title', 'id')),
                        ])
                        ->columnSpan(4)
                        ->columns(4),
                    Forms\Components\Section::make('Ticket Files')
                        ->schema([
                            Forms\Components\FileUpload::make('attachments')
                                ->disabled(true)
                                ->dehydrated(true)
                                ->openable()
                                ->columnSpanFull()
                                ->multiple(),
                        ])
                        ->columnSpan(4)
                        ->columns(3),
                    Forms\Components\Section::make('Ticket Work Order')
                        ->disabled()
                        ->schema([
                            Forms\Components\Select::make('work_order')
                                ->options(function ($record) {
                                    return Self::getTicketOrderType($record);
                                }),
                            Forms\Components\Select::make('sub_work_order')
                                ->options(function ($record) {
                                    return Self::getTicketSubOrderType($record);
                                }),
                            Forms\Components\Select::make('status')
                                ->hidden(!(auth()->user()->can('view_status_ticket')))
                                ->dehydrated(true)
                                ->options(
                                    Self::getTicketStatus()
                                ),
                            Forms\Components\Select::make('handler')
                                ->hidden(!(auth()->user()->can('view_handler_ticket')))
                                ->dehydrated(true)
                                ->options(
                                    Self::getTicketHandler()
                                ),
                        ])
                        ->columnSpan(4)
                        ->columns(4),
                    Forms\Components\Section::make('Ticket Users')
                        ->schema([
                            Forms\Components\Select::make('customer')
                                ->hidden(!(auth()->user()->can('view_customers_ticket')))
                                ->multiple()
                                ->relationship('customer', 'email')
                                ->disabled(true)
                                ->dehydrated(true),
                            Forms\Components\Select::make('technical_support')
                                ->hidden(!(auth()->user()->can('view_technical_supports_ticket')))
                                ->multiple()
                                ->relationship('technicalSupport', 'email')
                                ->disabled(true)
                                ->dehydrated(true),
                            Forms\Components\Select::make('high_technical_support')
                                ->hidden(!(auth()->user()->can('view_high_technical_supports_ticket')))
                                ->multiple()
                                ->relationship('highTechnicalSupport', 'email')
                                ->disabled(true)
                                ->dehydrated(true),
                        ])
                        ->columnSpan(4)
                        ->columns(3),
                    Forms\Components\Section::make('Ticket Proccess Time')
                        ->schema([
                            Forms\Components\DateTimePicker::make('start_at')
                                ->hidden(!(auth()->user()->can('view_start_at_ticket')))
                                ->live()
                                ->disabled(true)
                                ->dehydrated(true),
                            Forms\Components\DateTimePicker::make('end_at')
                                ->hidden(!(auth()->user()->can('view_end_at_ticket')))
                                ->live()
                                ->disabled(true)
                                ->dehydrated(true),
                        ])
                        ->columnSpan(4)
                        ->columns(2),
                ])
                ->columnSpan(3)
                ->columns(4),
        ];
    }

    private static function getViewForm(): array
    {
        return [
            Forms\Components\TextInput::make('deleted_at')
                ->visible(function ($record) {
                    return !is_null($record->deleted_at);
                })
                ->columnSpan(3)
                ->label('Archived At'),
            Forms\Components\Section::make('Ticket Info')
                ->schema([
                    Forms\Components\TextInput::make('ticket_identifier'),
                    Forms\Components\TextInput::make('title'),
                    Forms\Components\TextInput::make('ne_product'),
                    Forms\Components\TextInput::make('sw_version'),
                    Forms\Components\TextInput::make('company')
                        ->columnSpanFull(),
                    Forms\Components\Textarea::make('description')
                        ->columnSpanFull(),
                    Forms\Components\Section::make('Ticket Meta Data')
                        ->schema([
                            Forms\Components\Select::make('type_id')
                                ->label('Type')
                                ->options(Type::all()->pluck('title', 'id')),
                            Forms\Components\Select::make('priority_id')
                                ->label('Priority')
                                ->options(Priority::all()->pluck('title', 'id')),
                            Forms\Components\Select::make('department_id')
                                ->label('Department')
                                ->options(Department::all()->pluck('title', 'id')),
                            Forms\Components\Select::make('category_id')
                                ->label('Technology')
                                ->options(Category::all()->pluck('title', 'id')),
                        ])
                        ->columnSpan(4)
                        ->columns(4),
                    Forms\Components\Section::make('Ticket Files')
                        ->schema([
                            Forms\Components\FileUpload::make('attachments')
                                ->multiple()
                                ->downloadable()
                                ->columnSpanFull()
                                ->openable(),
                        ])
                        ->columnSpan(4)
                        ->columns(3),
                    Forms\Components\Section::make('Ticket Work Order')
                        ->schema([
                            Forms\Components\Select::make('work_order')
                                ->options(function ($record) {
                                    return Self::getTicketOrderType($record);
                                }),
                            Forms\Components\Select::make('sub_work_order')
                                ->options(function ($record) {
                                    return Self::getTicketSubOrderType($record);
                                }),
                            Forms\Components\Select::make('status')
                                ->hidden(!(auth()->user()->can('view_status_ticket')))
                                ->options(
                                    Self::getTicketStatus()
                                ),
                            Forms\Components\Select::make('handler')
                                ->hidden(!(auth()->user()->can('view_handler_ticket')))
                                ->options(
                                    Self::getTicketHandler()
                                ),
                        ])
                        ->columnSpan(4)
                        ->columns(4),
                    Forms\Components\Section::make('Ticket Users')
                        ->schema([
                            Forms\Components\Select::make('customer')
                                ->hidden(!(auth()->user()->can('view_customers_ticket')))
                                ->multiple()
                                ->relationship('customer', 'email'),
                            Forms\Components\Select::make('technical_support')
                                ->hidden(!(auth()->user()->can('view_technical_supports_ticket')))
                                ->multiple()
                                ->relationship('technicalSupport', 'email'),
                            Forms\Components\Select::make('high_technical_support')
                                ->hidden(!(auth()->user()->can('view_high_technical_supports_ticket')))
                                ->multiple()
                                ->relationship('highTechnicalSupport', 'email'),
                        ])
                        ->columnSpan(4)
                        ->columns(3),
                    Forms\Components\Section::make('Ticket Proccess Time')
                        ->schema([
                            Forms\Components\DateTimePicker::make('start_at')
                                ->hidden(!(auth()->user()->can('view_start_at_ticket'))),
                            Forms\Components\DateTimePicker::make('end_at')
                                ->hidden(!(auth()->user()->can('view_end_at_ticket'))),
                        ])
                        ->columnSpan(4)
                        ->columns(2),
                ])
                ->columnSpan(3)
                ->columns(4),
        ];
    }

    private static function getEditAllCondition($record): bool
    {
        if (auth()->user()->can('can_edit_all_ticket')) {
            return false;
        } else {
            return !($record->customer->contains(auth()->user()->id));
        }
    }

    private static function getTicketOrderType($record): array
    {
        if (auth()->user()->can('view_all_order_type_ticket')) {
            if ($record->level_id == Level::where('code', 2)->first()->id) {
                return [
                    TicketWorkOrder::FEEDBACK_TO_TECHNICAL_SUPPORT->value => 'Feedback to Technical Support',
                    TicketWorkOrder::TECHNICAL_SUPPORT_TROUBLESHOOTING_ACTIVITY->value => 'Troubleshooting Activity',
                    TicketWorkOrder::TECHNICAL_SUPPORT_RESPONSE->value => 'Technical Support Response',
                    TicketWorkOrder::RESOLUTION_ACCEPTED_BY_TECHNICAL_SUPPORT->value => 'Resolution Accepted by Technical Support',
                    TicketWorkOrder::FEEDBACK_TO_CUSTOMER->value => 'Feedback to Customer',
                    TicketWorkOrder::CUSTOMER_TROUBLESHOOTING_ACTIVITY->value => 'Troubleshooting Activity',
                    TicketWorkOrder::CUSTOMER_RESPONSE->value => 'Customer Response',
                    TicketWorkOrder::WORKAROUND_ACCEPTED_BY_CUSTOMER->value => 'Workaround Accepted By Customer',
                    TicketWorkOrder::RESOLUTION_ACCEPTED_BY_CUSTOMER->value => 'Resolution Accepted by Customer',
                ];
            } else {
                return [
                    TicketWorkOrder::FEEDBACK_TO_CUSTOMER->value => 'Feedback to Customer',
                    TicketWorkOrder::CUSTOMER_TROUBLESHOOTING_ACTIVITY->value => 'Troubleshooting Activity',
                    TicketWorkOrder::CUSTOMER_RESPONSE->value => 'Customer Response',
                    TicketWorkOrder::WORKAROUND_ACCEPTED_BY_CUSTOMER->value => 'Workaround Accepted By Customer',
                    TicketWorkOrder::RESOLUTION_ACCEPTED_BY_CUSTOMER->value => 'Resolution Accepted by Customer',
                ];
            }
            return [];
        }
        if (auth()->user()->can('view_customer_order_type_ticket')) {
            return [
                TicketWorkOrder::FEEDBACK_TO_CUSTOMER->value => 'Feedback to Customer',
                TicketWorkOrder::CUSTOMER_TROUBLESHOOTING_ACTIVITY->value => 'Troubleshooting Activity',
                TicketWorkOrder::CUSTOMER_RESPONSE->value => 'Customer Response',
                TicketWorkOrder::WORKAROUND_ACCEPTED_BY_CUSTOMER->value => 'Workaround Accepted By Customer',
                TicketWorkOrder::RESOLUTION_ACCEPTED_BY_CUSTOMER->value => 'Resolution Accepted by Customer',
            ];
        }
        if (auth()->user()->can('view_high_technical_support_order_type_ticket')) {
            return [
                TicketWorkOrder::FEEDBACK_TO_TECHNICAL_SUPPORT->value => 'Feedback to Technical Support',
                TicketWorkOrder::TECHNICAL_SUPPORT_TROUBLESHOOTING_ACTIVITY->value => 'Troubleshooting Activity',
                TicketWorkOrder::TECHNICAL_SUPPORT_RESPONSE->value => 'Technical Support Response',
                TicketWorkOrder::RESOLUTION_ACCEPTED_BY_TECHNICAL_SUPPORT->value => 'Resolution Accepted by Technical Support',
            ];
        }
        return [];
    }

    private static function getTicketSubOrderType($record): array
    {
        if (auth()->user()->can('view_all_order_type_ticket')) {
            if ($record->level_id == Level::where('code', 2)->first()->id) {
                return [
                    TicketSubWorkOrder::CUSTOMER_INFORMATION_REQUIRED->value => 'Customer Information Required',
                    TicketSubWorkOrder::WORKAROUND_CUSTOMER_INFORMATION->value => 'Workaround Customer Information',
                    TicketSubWorkOrder::FINAL_CUSTOMER_INFORMATION->value => 'Final Customer Information',
                    TicketSubWorkOrder::TECHNICAL_SUPPORT_INFORMATION_REQUIRED->value => 'Technical Support Information Required',
                    TicketSubWorkOrder::WORKAROUND_TECHNICAL_SUPPORT_INFORMATION->value => 'Workaround Technical Support Information',
                    TicketSubWorkOrder::FINAL_CUSTOMER_INFORMATION->value => 'Final Technical Support Information',
                ];
            } else {
                return [
                    TicketSubWorkOrder::CUSTOMER_INFORMATION_REQUIRED->value => 'Customer Information Required',
                    TicketSubWorkOrder::WORKAROUND_CUSTOMER_INFORMATION->value => 'Workaround Customer Information',
                    TicketSubWorkOrder::FINAL_CUSTOMER_INFORMATION->value => 'Final Customer Information',
                ];
            }
            return [];
        }
        if (auth()->user()->can('view_customer_order_type_ticket')) {
            return [
                TicketSubWorkOrder::CUSTOMER_INFORMATION_REQUIRED->value => 'Customer Information Required',
                TicketSubWorkOrder::WORKAROUND_CUSTOMER_INFORMATION->value => 'Workaround Customer Information',
                TicketSubWorkOrder::FINAL_CUSTOMER_INFORMATION->value => 'Final Customer Information',
            ];
        }
        if (auth()->user()->can('view_support_order_type_ticket')) {
            return [
                TicketSubWorkOrder::TECHNICAL_SUPPORT_INFORMATION_REQUIRED->value => 'Technical Support Information Required',
                TicketSubWorkOrder::WORKAROUND_TECHNICAL_SUPPORT_INFORMATION->value => 'Workaround Technical Support Information',
                TicketSubWorkOrder::FINAL_CUSTOMER_INFORMATION->value => 'Final Technical Support Information',
            ];
        }
        return [];
    }

    private static function getTicketStatus(): array
    {
        return [
            TicketStatus::IN_PROGRESS->value => 'In Progress',
            TicketStatus::CUSTOMER_PENDING->value => 'Customer Pending',
            TicketStatus::CUSTOMER_UNDER_MONITORING->value => 'Under Monitoring',
            TicketStatus::CLOSED->value => 'Closed',
            TicketStatus::HIGHT_LEVEL_SUPPORT_PENDING->value => 'Hight Level Support Pending',
            TicketStatus::TECHNICAL_SUPPORT_PENDING->value => 'Technical Support Pending',
            TicketStatus::TECHNICAL_SUPPORT_UNDER_MONITORING->value => 'Under Monitoring',
        ];
    }

    private static function getTicketHandler(): array
    {
        return [
            TicketHandler::CUSTOMER->value => 'Customer',
            TicketHandler::TECHNICAL_SUPPORT->value => 'Technical Support',
            TicketHandler::HIGH_LEVEL_SUPPORT->value => 'High level support',
        ];
    }
}
