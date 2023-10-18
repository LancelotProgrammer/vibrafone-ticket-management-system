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
use App\Models\Priority;
use App\Models\Ticket;
use App\Models\TicketHistory;
use App\Models\Type;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Carbon\Carbon;
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
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

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

            'view_all',
            'export',
            'edit_all',

            'manage_user',
            'manage_technical_support_attachments',
            'manage_high_technical_support_attachments',

            'esclate',
            'assign',
            'create_work_order_type',
            'archive',

            'edit_history_date',
            'view_history',
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                //NOTE: create form
                Forms\Components\Section::make('Create Info')
                    ->visible(function (Page $livewire) {
                        return $livewire instanceof CreateTicket;
                    })
                    ->schema([
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
                                Forms\Components\Textarea::make('description')
                                    ->required()
                                    ->columnSpanFull()
                                    ->maxLength(512),
                                Forms\Components\Section::make('Ticket Meta Data')
                                    ->schema([
                                        Forms\Components\Select::make('type_id')
                                            ->required()
                                            ->label('Type')
                                            ->options(Type::all()->pluck('title', 'id')),
                                        Forms\Components\Select::make('priority_id')
                                            ->required()
                                            ->label('Priority')
                                            ->options(Priority::all()->pluck('title', 'id')),
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
                                                if (auth()->user()->hasRole(['manager', 'super_admin'])) {
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
                                        Forms\Components\FileUpload::make('customer_attachments')
                                            ->acceptedFileTypes([
                                                'application/pdf',
                                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                                'image/jpeg',
                                                'image/png',
                                            ])
                                            ->openable()
                                            ->columnSpanFull()
                                            ->multiple(),
                                    ])
                                    ->columnSpan(4)
                                    ->columns(3),
                            ])
                            ->columnSpan(3)
                            ->columns(4),
                    ]),

                //NOTE: edit form
                Forms\Components\Section::make('Edit Info')
                    ->visible(function (Page $livewire) {
                        return $livewire instanceof EditTicket;
                    })
                    ->schema([
                        Forms\Components\Section::make('Ticket Info')
                            ->schema([
                                Forms\Components\TextInput::make('ticket_identifier')
                                    ->disabled(true)
                                    ->dehydrated(true)
                                    ->required()
                                    ->maxLength(64),
                                Forms\Components\TextInput::make('title')
                                    ->disabled(!(auth()->user()->can('edit_all_ticket')))
                                    ->required()
                                    ->maxLength(64),
                                Forms\Components\TextInput::make('ne_product')
                                    ->disabled(!(auth()->user()->can('edit_all_ticket')))
                                    ->required()
                                    ->maxLength(64),
                                Forms\Components\TextInput::make('sw_version')
                                    ->disabled(!(auth()->user()->can('edit_all_ticket')))
                                    ->required()
                                    ->maxLength(64),
                                Forms\Components\Textarea::make('description')
                                    ->disabled(!(auth()->user()->can('edit_all_ticket')))
                                    ->required()
                                    ->columnSpanFull()
                                    ->maxLength(512),
                                Forms\Components\Section::make('Ticket Meta Data')
                                    ->schema([
                                        Forms\Components\Select::make('type_id')
                                            ->disabled(true)
                                            ->dehydrated(true)
                                            ->label('Type')
                                            ->options(Type::all()->pluck('title', 'id')),
                                        Forms\Components\Select::make('priority_id')
                                            ->disabled(true)
                                            ->dehydrated(true)
                                            ->label('Priority')
                                            ->options(Priority::all()->pluck('title', 'id')),
                                        Forms\Components\Select::make('department_id')
                                            ->disabled(true)
                                            ->dehydrated(true)
                                            ->required()
                                            ->label('Department')
                                            ->options(Department::all()->where('title', '!=', 'default')->pluck('title', 'id')),
                                        Forms\Components\Select::make('category_id')
                                            ->disabled(true)
                                            ->dehydrated(true)
                                            ->required()
                                            ->label('Category')
                                            ->options(Category::all()->pluck('title', 'id')),
                                    ])
                                    ->columnSpan(4)
                                    ->columns(4),
                                Forms\Components\Section::make('Ticket Files')
                                    ->schema([
                                        Forms\Components\FileUpload::make('customer_attachments')
                                            ->disabled(true)
                                            ->dehydrated(true)
                                            ->acceptedFileTypes([
                                                'application/pdf',
                                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                                'image/jpeg',
                                                'image/png',
                                            ])
                                            ->openable()
                                            ->columnSpanFull()
                                            ->multiple(),
                                        Forms\Components\FileUpload::make('technical_support_attachments')
                                            ->disabled(!(auth()->user()->can('manage_technical_support_attachments_ticket')))
                                            ->acceptedFileTypes([
                                                'application/pdf',
                                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                                'image/jpeg',
                                                'image/png',
                                            ])
                                            ->openable()
                                            ->downloadable()
                                            ->columnSpanFull()
                                            ->multiple(),
                                        Forms\Components\FileUpload::make('high_technical_support_attachments')
                                            ->disabled(!(auth()->user()->can('manage_high_technical_support_attachments_ticket')))
                                            ->acceptedFileTypes([
                                                'application/pdf',
                                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                                'image/jpeg',
                                                'image/png',
                                            ])
                                            ->openable()
                                            ->downloadable()
                                            ->columnSpanFull()
                                            ->multiple(),
                                    ])
                                    ->columnSpan(4)
                                    ->columns(3),
                                Forms\Components\Section::make('Ticket Work Order')
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
                                                    TicketWorkOrder::WORKAROUND_ACCEPTED_BY_CUSTOMER->value => 'Workaround Accepted By Customer',
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
                                    ->schema([
                                        Forms\Components\Select::make('customer')
                                            ->multiple()
                                            ->relationship('customer', 'email')
                                            ->disabled(true)
                                            ->dehydrated(true),
                                        Forms\Components\Select::make('technical_support')
                                            ->multiple()
                                            ->relationship('technicalSupport', 'email')
                                            ->disabled(true)
                                            ->dehydrated(true),
                                        Forms\Components\Select::make('high_technical_support')
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
                                            ->live()
                                            ->disabled(true)
                                            ->dehydrated(true),
                                        Forms\Components\DateTimePicker::make('end_at')
                                            ->live()
                                            ->disabled(true)
                                            ->dehydrated(true),
                                    ])
                                    ->columnSpan(4)
                                    ->columns(2),
                            ])
                            ->columnSpan(3)
                            ->columns(4),
                    ]),

                //NOTE: view form
                Forms\Components\Section::make('View Info')
                    ->visible(function (Page $livewire) {
                        return $livewire instanceof ViewTicket;
                    })
                    ->schema([
                        Forms\Components\TextInput::make('deleted_at')
                            ->visible(function ($record) {
                                return !is_null($record->deleted_at);
                            })
                            ->columnSpan(3)
                            ->label('Archive At'),
                        Forms\Components\Section::make('Ticket Info')
                            ->schema([
                                Forms\Components\TextInput::make('ticket_identifier'),
                                Forms\Components\TextInput::make('title'),
                                Forms\Components\TextInput::make('ne_product'),
                                Forms\Components\TextInput::make('sw_version'),
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
                                            ->options(Department::all()->where('title', '!=', 'default')->pluck('title', 'id')),
                                        Forms\Components\Select::make('category_id')
                                            ->label('Category')
                                            ->options(Category::all()->pluck('title', 'id')),
                                    ])
                                    ->columnSpan(4)
                                    ->columns(4),
                                Forms\Components\Section::make('Ticket Files')
                                    ->schema([
                                        Forms\Components\FileUpload::make('customer_attachments')
                                            ->downloadable()
                                            ->openable(),
                                        Forms\Components\FileUpload::make('technical_support_attachments')
                                            ->downloadable()
                                            ->openable(),
                                        Forms\Components\FileUpload::make('high_technical_support_attachments')
                                            ->downloadable()
                                            ->openable(),
                                    ])
                                    ->columnSpan(4)
                                    ->columns(3),
                                Forms\Components\Section::make('Ticket Work Order')
                                    ->schema([
                                        Forms\Components\Select::make('work_order')
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
                                                    TicketWorkOrder::WORKAROUND_ACCEPTED_BY_CUSTOMER->value => 'Workaround Accepted By Customer',
                                                    TicketWorkOrder::RESOLUTION_ACCEPTED_BY_TECHNICAL_SUPPORT->value => 'Resolution Accepted by Technical Support',
                                                ],
                                            ]),
                                        Forms\Components\Select::make('sub_work_order')
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
                                            ->options([
                                                TicketHandler::CUSTOMER->value => 'Customer',
                                                TicketHandler::TECHNICAL_SUPPORT->value => 'Technical Support',
                                                TicketHandler::HIGH_LEVEL_SUPPORT->value => 'High level support',
                                            ]),
                                    ])
                                    ->columnSpan(4)
                                    ->columns(4),
                                Forms\Components\Section::make('Ticket Users')
                                    ->schema([
                                        Forms\Components\Select::make('customer')
                                            ->multiple()
                                            ->relationship('customer', 'email'),
                                        Forms\Components\Select::make('technical_support')
                                            ->multiple()
                                            ->relationship('technicalSupport', 'email'),
                                        Forms\Components\Select::make('high_technical_support')
                                            ->multiple()
                                            ->relationship('highTechnicalSupport', 'email'),
                                    ])
                                    ->columnSpan(4)
                                    ->columns(3),
                                Forms\Components\Section::make('Ticket Proccess Time')
                                    ->schema([
                                        Forms\Components\DateTimePicker::make('start_at'),
                                        Forms\Components\DateTimePicker::make('end_at'),
                                    ])
                                    ->columnSpan(4)
                                    ->columns(2),
                            ])
                            ->columnSpan(3)
                            ->columns(4),
                    ]),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('10s')
            ->modifyQueryUsing(function (Builder $query) {
                // NOTE: here we filter the tickets based on user type / department / level
                if (auth()->user()->can('view_all_ticket')) {
                    return $query;
                }
                if (auth()->user()->level_id == 3 && auth()->user()->can('view_all_ticket')) {
                    return $query
                        ->where('level_id', auth()->user()->level_id);
                }
                if (auth()->user()->level_id == 3) {
                    return $query
                        ->where('department_id', auth()->user()->department_id)
                        ->where('level_id', auth()->user()->level_id);
                }
                if (auth()->user()->level_id == 2 || auth()->user()->level_id == 1) {
                    return $query
                        ->where('department_id', auth()->user()->department_id);
                }
                return $query
                    ->where('department_id', auth()->user()->department_id);
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
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(function ($record) {
                        if ($record->status == TicketStatus::CLOSED->value) {
                            return false;
                        }
                        if (auth()->user()->hasRole(['manager', 'super_admin'])) {
                            return true;
                        }
                        if (
                            $record->technicalSupport->contains(auth()->user()->id) ||
                            $record->highTechnicalSupport->contains(auth()->user()->id)
                        ) {
                            return true;
                        }
                        if ($record->customer->contains(auth()->user()->id)) {
                            return false;
                        }
                    }),
                Tables\Actions\Action::make('archive')
                    ->hidden(!(auth()->user()->can('archive_ticket')))
                    ->requiresConfirmation()
                    ->visible(function (Ticket $record) {
                        return is_null($record->deleted_at) && $record->status == TicketStatus::CLOSED->value;
                    })
                    ->action(function (Ticket $record): void {
                        DB::transaction(function () use ($record) {
                            $record->deleted_at = Carbon::now()->toDateTimeString();
                            $ticketHistory = new TicketHistory([
                                'ticket_id' => $record->id,
                                'title' => 'ticket has been archived',
                                'owner' => auth()->user()->email,
                                'work_order' => $record->work_order,
                                'sub_work_order' => $record->sub_work_order,
                                'status' => $record->status,
                                'handler' => $record->handler,
                                'created_at' => Carbon::now()->toDateTimeString(),
                            ]);
                            $record->ticketHistory()->save($ticketHistory);
                            $record->save();
                        });
                        Notification::make()
                            ->title('Ticket has been archived')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('assgin')
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
                        if (auth()->user()->hasRole(['manager', 'super_admin'])) {
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
                                if (auth()->user()->hasRole(['manager', 'super_admin'])) {
                                    $record->technicalSupport()->attach(auth()->user()->id);
                                }
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
                                    'created_at' => Carbon::now()->toDateTimeString(),
                                ]);
                                $record->ticketHistory()->save($ticketHistory);
                                if (is_null($record->start_at)) {
                                    $record->start_at = Carbon::now()->toDateTimeString();
                                }
                                $record->save();
                            });
                            Notification::make()
                                ->title('ticket assgined to you')
                                ->success()
                                ->send();
                        } catch (Exception $e) {
                            Notification::make()
                                ->title('Error assging ticket')
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

    public static function canEdit(Model $record): bool
    {
        if (auth()->user()->can('edit_all_ticket')) {
            return true;
        } else {
            return $record->department_id == auth()->user()->department_id;
        }
    }
}
