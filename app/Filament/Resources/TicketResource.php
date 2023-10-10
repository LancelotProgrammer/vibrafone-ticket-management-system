<?php

namespace App\Filament\Resources;

use App\Enums\TicketHandler;
use App\Enums\TicketStatus;
use App\Enums\TicketSubWorkOrder;
use App\Enums\TicketWorkOrder;
use App\Filament\Resources\TicketResource\Pages;
use App\Filament\Resources\TicketResource\Pages\EditTicket;
use App\Filament\Resources\TicketResource\RelationManagers;
use App\Filament\Resources\TicketResource\RelationManagers\TicketHistoryRelationManager;
use App\Models\Category;
use App\Models\Department;
use App\Models\Priority;
use App\Models\Ticket;
use App\Models\Type;
use App\Models\User;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class TicketResource extends Resource
{
    protected static ?string $model = Ticket::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('deleted_at')
                    ->visible(function ($record, Page $livewire) {
                        if ($livewire instanceof EditTicket) {
                            return !is_null($record->deleted_at);
                        }
                    })
                    ->disabled()
                    ->columnSpan(3)
                    ->label('Archive At'),
                Forms\Components\Section::make('Ticket Info')
                    ->disabled(function ($record, Page $livewire) {
                        if ($livewire instanceof EditTicket) {
                            return !is_null($record->deleted_at);
                        }
                    })
                    ->schema([
                        Forms\Components\TextInput::make('ticket_identifier')
                            ->disabled(true)
                            ->dehydrated(true)
                            ->required()
                            ->maxLength(64),
                        Forms\Components\TextInput::make('title')
                            ->disabled(function ($record, Page $livewire) {
                                if ($livewire instanceof EditTicket) {
                                    return !is_null($record->title);
                                }
                            })
                            ->required()
                            ->maxLength(64),
                        Forms\Components\TextInput::make('ne_product')
                            ->disabled(function ($record, Page $livewire) {
                                if ($livewire instanceof EditTicket) {
                                    return !is_null($record->ne_product);
                                }
                            })
                            ->required()
                            ->maxLength(64),
                        Forms\Components\TextInput::make('sw_version')
                            ->disabled(function ($record, Page $livewire) {
                                if ($livewire instanceof EditTicket) {
                                    return !is_null($record->sw_version);
                                }
                            })
                            ->required()
                            ->maxLength(64),
                        Forms\Components\Textarea::make('description')
                            ->disabled(function ($record, Page $livewire) {
                                if ($livewire instanceof EditTicket) {
                                    return !is_null($record->description);
                                }
                            })
                            ->required()
                            ->columnSpanFull()
                            ->maxLength(512),
                        Forms\Components\Section::make('Ticket Meta Data')
                            ->schema([
                                Forms\Components\Select::make('type_id')
                                    ->disabled(function ($record, Page $livewire) {
                                        if ($livewire instanceof EditTicket) {
                                            return !is_null($record->type_id);
                                        }
                                    })
                                    ->required()
                                    ->label('Type')
                                    ->options(Type::all()->pluck('title', 'id')),
                                Forms\Components\Select::make('priority_id')
                                    ->disabled(function ($record, Page $livewire) {
                                        if ($livewire instanceof EditTicket) {
                                            return !is_null($record->priority_id);
                                        }
                                    })
                                    ->required()
                                    ->label('Priority')
                                    ->options(Priority::all()->pluck('title', 'id')),
                                Forms\Components\Select::make('department_id')
                                    ->disabled(function ($record, Page $livewire) {
                                        if ($livewire instanceof EditTicket) {
                                            return !is_null($record->department_id);
                                        }
                                    })
                                    ->required()
                                    ->label('Department')
                                    ->live()
                                    ->afterStateUpdated(function ($state, $set) {
                                        $ticketIdentifier = null;
                                        do {
                                            $ticketIdentifier = Department::where('id', $state)->first()->code . '-' . floor(rand(500, 999) * 10000) . '-' . rand(500, 999)  . '-' . rand(500, 999);
                                        } while (is_null(Ticket::where('ticket_identifier', $ticketIdentifier)->get()));
                                        $set(
                                            'ticket_identifier',
                                            $ticketIdentifier
                                        );
                                    })
                                    ->options(Department::all()->pluck('title', 'id')),
                                Forms\Components\Select::make('category_id')
                                    ->disabled(function ($record, Page $livewire) {
                                        if ($livewire instanceof EditTicket) {
                                            return !is_null($record->category_id);
                                        }
                                    })
                                    ->required()
                                    ->label('Category')
                                    ->options(Category::all()->pluck('title', 'id')),
                                Forms\Components\FileUpload::make('attachments')
                                    ->disabled(function ($record, Page $livewire) {
                                        if ($livewire instanceof EditTicket) {
                                            return true;
                                        }
                                    })
                                    ->acceptedFileTypes([
                                        'application/pdf',
                                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                        'image/jpeg',
                                        'image/png',
                                    ])
                                    ->downloadable()
                                    ->multiple()
                                    ->columnSpanFull(),


                            ])
                            ->columnSpan(4)
                            ->columns(4),
                        Forms\Components\Section::make('Ticket Work Order')
                            ->visible(function (Page $livewire) {
                                return $livewire instanceof EditTicket;
                            })
                            ->schema([
                                Forms\Components\Select::make('work_order')
                                    ->disabled()
                                    ->options([
                                        'Customer' => [
                                            TicketWorkOrder::FEEDBACK_TO_CUSTOMER->value => 'Feedback to Customer',
                                            TicketWorkOrder::CUSTOMER_TROUBLESHOOTING_ACTIVITY->value => 'Troubleshooting Activity',
                                            TicketWorkOrder::CUSTOMER_RESPONSE->value => 'Customer Response',
                                            TicketWorkOrder::RESOLUTION_ACCEPTED_BY_CUSTOMER->value => 'Resolution Accepted by Customer',
                                        ],
                                        'Technical Support' => [
                                            TicketWorkOrder::FEEDBACK_TO_TECHNICAL_SUPPORT->value => 'Feedback to Technical Support',
                                            TicketWorkOrder::TECHNICAL_SUPPORT_TROUBLESHOOTING_ACTIVITY->value => 'Troubleshooting Activity',
                                            TicketWorkOrder::TECHNICAL_SUPPORT_RESPONSE->value => 'Technical Support Response',
                                            TicketWorkOrder::RESOLUTION_ACCEPTED_BY_TECHNICAL_SUPPORT->value => 'Resolution Accepted by Technical Support',
                                        ],
                                    ]),
                                Forms\Components\Select::make('sub_work_order')
                                    ->disabled()
                                    ->options([
                                        'Feedback to Customer' => [
                                            TicketSubWorkOrder::CUSTOMER_INFORMATION_REQUIRED->value => 'Customer Information Required',
                                            TicketSubWorkOrder::WORKAROUND_CUSTOMER_INFORMATION->value => 'Workaround Customer Information',
                                            TicketSubWorkOrder::FINAL_CUSTOMER_INFORMATION->value => 'Final Customer Information',
                                        ],
                                        'Feedback to Technical Support' => [
                                            TicketSubWorkOrder::TECHNICAL_SUPPORT_INFORMATION_REQUIRED->value => 'Technical Support Information Required',
                                            TicketSubWorkOrder::WORKAROUND_TECHNICAL_SUPPORT_INFORMATION->value => 'Workaround Technical Support Information',
                                            TicketSubWorkOrder::FINAL_CUSTOMER_INFORMATION->value => 'Final Technical Support Information',
                                        ],
                                    ]),
                                Forms\Components\Select::make('status')
                                    ->disabled()
                                    ->options([
                                        TicketStatus::IN_PROGRESS->value => 'In Progress',
                                        TicketStatus::CUSTOMER_PENDING->value => 'Customer Pending',
                                        TicketStatus::CUSTOMER_UNDER_MONITORING->value => 'Under Monitoring',
                                        TicketStatus::CLOSED->value => 'Closed',
                                        TicketStatus::HIGHT_LEVEL_SUPPORT_PENDING->value => 'Hight Level Support Pending',
                                        TicketStatus::TECHNICAL_SUPPORT_PENDING->value => 'Technical Support Pending',
                                        TicketStatus::TECHNICAL_SUPPORT_UNDER_MONITORING->value => 'Under Monitoring',
                                    ]),
                                Forms\Components\Select::make('handler')
                                    ->disabled()
                                    ->options([
                                        TicketHandler::CUSTOMER->value => 'Customer',
                                        TicketHandler::TECHNICAL_SUPPORT->value => 'Technical Support',
                                        TicketHandler::HIGH_LEVEL_SUPPORT->value => 'High level support',
                                    ]),
                            ])
                            ->columnSpan(4)
                            ->columns(4),
                        Forms\Components\Section::make('Ticket Users')
                            ->visible(function (Page $livewire) {
                                return $livewire instanceof EditTicket;
                            })
                            ->schema([
                                Forms\Components\Select::make('customer_user_id')
                                    ->disabled(true)
                                    ->label('Customer')
                                    ->options(User::all()->pluck('email', 'id')),
                                Forms\Components\Select::make('technical_support_user_id')
                                    ->disabled(function ($record) {
                                        return !is_null($record->technical_support_user_id);
                                    })
                                    ->dehydrated(true)
                                    ->live()
                                    ->afterStateUpdated(function ($set) {
                                        $set('start_at', Carbon::now()->toDateTimeString());
                                    })
                                    ->label('Technical Support')
                                    ->options(User::all()->pluck('email', 'id')),
                                Forms\Components\Select::make('high_technical_support_user_id')
                                    ->disabled(true)
                                    ->dehydrated(true)
                                    ->label('High Technical Support')
                                    ->options(User::all()->pluck('email', 'id')),
                            ])
                            ->columnSpan(4)
                            ->columns(3),
                        Forms\Components\Section::make('Ticket Proccess Time')
                            ->visible(function (Page $livewire) {
                                return $livewire instanceof EditTicket;
                            })
                            ->schema([
                                Forms\Components\DateTimePicker::make('start_at')
                                    ->disabled(true)
                                    ->dehydrated(true),
                                Forms\Components\DateTimePicker::make('end_at')
                                    ->disabled(true)
                                    ->dehydrated(true),
                            ])
                            ->columnSpan(4)
                            ->columns(2),
                    ])
                    ->columnSpan(3)
                    ->columns(4),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
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
                            ->icon('heroicon-m-user-circle'),
                        Tables\Columns\TextColumn::make('technicalSupport.email')
                            ->icon('heroicon-m-user-group'),
                        Tables\Columns\TextColumn::make('highTechnicalSupport.email')
                            ->icon('heroicon-m-user-plus'),
                    ]),
                    Stack::make([
                        Tables\Columns\TextColumn::make('status')
                            ->icon('heroicon-m-wrench'),
                        Tables\Columns\TextColumn::make('handler')
                            ->icon('heroicon-m-tag'),
                    ]),
                ]),
            ])
            ->filters([
                SelectFilter::make('handler')
                    ->options([
                        TicketHandler::CUSTOMER->value => 'Customer',
                        TicketHandler::TECHNICAL_SUPPORT->value => 'Technical Support',
                        TicketHandler::HIGH_LEVEL_SUPPORT->value => 'High level support',
                    ]),
                SelectFilter::make('status')
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
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    ExportBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Ticket Info')
                    ->schema([
                        TextEntry::make('ticket_identifier'),
                        TextEntry::make('title'),
                        TextEntry::make('ne_product'),
                        TextEntry::make('sw_version'),
                        TextEntry::make('description')
                            ->columnSpanFull(),
                        TextEntry::make('Ticket Meta Data')
                            ->schema([
                                TextEntry::make('type_id'),
                                TextEntry::make('priority_id'),
                                TextEntry::make('department_id'),
                                TextEntry::make('category_id'),
                            ])
                            ->columnSpan(4)
                            ->columns(4),
                        Section::make('Ticket Work Order')
                            ->schema([
                                TextEntry::make('work_order')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        TicketWorkOrder::FEEDBACK_TO_CUSTOMER->value => 'primary',
                                        TicketWorkOrder::CUSTOMER_TROUBLESHOOTING_ACTIVITY->value => 'primary',
                                        TicketWorkOrder::CUSTOMER_RESPONSE->value => 'primary',
                                        TicketWorkOrder::RESOLUTION_ACCEPTED_BY_CUSTOMER->value => 'primary',
                                        TicketWorkOrder::FEEDBACK_TO_TECHNICAL_SUPPORT->value => 'primary',
                                        TicketWorkOrder::TECHNICAL_SUPPORT_TROUBLESHOOTING_ACTIVITY->value => 'primary',
                                        TicketWorkOrder::TECHNICAL_SUPPORT_RESPONSE->value => 'primary',
                                        TicketWorkOrder::RESOLUTION_ACCEPTED_BY_TECHNICAL_SUPPORT->value => 'primary',
                                        default => 'primary'
                                    }),
                                TextEntry::make('sub_work_order')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        TicketSubWorkOrder::CUSTOMER_INFORMATION_REQUIRED->value => 'primary',
                                        TicketSubWorkOrder::WORKAROUND_CUSTOMER_INFORMATION->value => 'primary',
                                        TicketSubWorkOrder::FINAL_CUSTOMER_INFORMATION->value => 'primary',
                                        TicketSubWorkOrder::TECHNICAL_SUPPORT_INFORMATION_REQUIRED->value => 'primary',
                                        TicketSubWorkOrder::WORKAROUND_TECHNICAL_SUPPORT_INFORMATION->value => 'primary',
                                        TicketSubWorkOrder::FINAL_CUSTOMER_INFORMATION->value => 'primary',
                                        default => 'primary'
                                    }),
                                TextEntry::make('status')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        TicketStatus::IN_PROGRESS->value => 'primary',
                                        TicketStatus::CUSTOMER_PENDING->value => 'primary',
                                        TicketStatus::CUSTOMER_UNDER_MONITORING->value => 'primary',
                                        TicketStatus::CLOSED->value => 'primary',
                                        TicketStatus::HIGHT_LEVEL_SUPPORT_PENDING->value => 'primary',
                                        TicketStatus::TECHNICAL_SUPPORT_PENDING->value => 'primary',
                                        TicketStatus::TECHNICAL_SUPPORT_UNDER_MONITORING->value => 'primary',
                                        default => 'primary'
                                    }),
                                TextEntry::make('handler')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        TicketHandler::CUSTOMER->value => 'primary',
                                        TicketHandler::TECHNICAL_SUPPORT->value => 'primary',
                                        TicketHandler::HIGH_LEVEL_SUPPORT->value => 'primary',
                                        default => 'primary'
                                    }),
                            ])
                            ->columnSpan(4)
                            ->columns(4),
                        Section::make('Ticket Users')
                            ->schema([
                                TextEntry::make('customer_user_id'),
                                TextEntry::make('technical_support_user_id'),
                                TextEntry::make('high_technical_support_user_id'),
                            ])
                            ->columnSpan(4)
                            ->columns(3),
                        Section::make('Ticket Proccess Time')
                            ->schema([
                                TextEntry::make('start_at'),
                                TextEntry::make('end_at'),
                            ])
                            ->columnSpan(4)
                            ->columns(2),
                    ])
                    ->columnSpan(3)
                    ->columns(4),
            ])
            ->columns(3);
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
        ];
    }
}
