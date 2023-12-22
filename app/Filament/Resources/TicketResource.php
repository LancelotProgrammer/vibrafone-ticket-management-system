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
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
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

            // NOTE: all permissions below needs the {view_any} permission to be enabled

            'view', // NOTE: enables users to view the ticket details | alternative name: view details
            'view_any',
            'create',
            'update', // NOTE: enables users to interact with a ticket | alternative name: access
            'delete',

            'can_view_all', // NOTE: this permission make the user ignores {can_ignore_level_when_view / can_ignore_department_when_view}
            'can_ignore_level_when_view',
            'can_ignore_department_when_view',

            'can_edit_any_info', // NOTE: enables users to edit the unlocked fields of any ticket at any condition | depends on the update permission filter
            'can_view_any_details', // NOTE: enables users to view ticket details at any condition | depends on the view permission filter
            'can_access_any', // NOTE: enables users to interact with any ticket only if it is enabled | depends on the view permission filter

            'can_archive',
            'can_export_excel',
            'can_export_pdf',
            'can_download_all_files',

            'view_handler',
            'view_status',
            'view_created_at',
            'view_escalated_to_high_technical_support_at',
            'view_escalated_to_external_technical_support_at',
            'view_start_at',
            'view_end_at',
            'view_archived_at',
            'view_customers',
            'view_technical_supports',
            'view_high_technical_supports',
            'view_external_technical_supports',
            'view_all_order_type', // NOTE: this permission make the user ignores {view_customer_order_type / view_technical_support_order_type /  view_high_technical_support_order_type / view_external_technical_support_order_type}
            'view_customer_order_type',
            'view_technical_support_order_type',
            'view_high_technical_support_order_type',
            'view_external_technical_support_order_type',

            'can_filter_table',
            'can_not_self_assign', // NOTE: this permission is only for users who are not managers
            'can_select_any_department', // NOTE: this permission needs the {create} permission to be enabled

            // NOTE: all permissions below needs the {update} permission to be enabled

            'add_technical_support', // NOTE: this permission is only managers
            'remove_technical_support', // NOTE: this permission is only managers
            'add_high_technical_support', // NOTE: this permission is only managers
            'remove_high_technical_support', // NOTE: this permission is only managers
            'add_external_technical_support', // NOTE: this permission is only managers
            'remove_external_technical_support', // NOTE: this permission is only managers

            'can_escalate_to_high_technical_support',
            'can_escalate_to_external_technical_support',
            'can_cancel',
            'can_activate',
            'can_assign_technical_support',
            'can_be_assigned_as_technical_support',
            'can_be_assigned_as_high_technical_support',
            'can_be_assigned_as_external_technical_support',

            'create_work_order_type',
            // NOTE: all permissions below need the {create_work_order_type} permission to be enabled as well as taking into consideration the ticket level
            'view_all_create_order_type', // this permission make the user ignores {view_customer_request_create_order_type / view_customer_response_create_order_type / view_technical_support_request_create_order_type / view_technical_support_response_create_order_type / view_high_technical_support_request_create_order_type / view_high_technical_support_response_create_order_type }
            'view_customer_request_create_order_type',
            'view_customer_response_create_order_type',
            'view_technical_support_request_create_order_type',
            'view_technical_support_response_create_order_type',
            'view_high_technical_support_request_create_order_type',
            'view_high_technical_support_response_create_order_type',
            'send_email_in_order_type',

            'view_history',
            // NOTE: all permissions below need the {view_history} permission to be enabled
            'edit_history_date', // NOTE: this permission is only for admins
            'view_history_attachments',
            'delete_history', // NOTE: this permission is only for admins
            'view_history_all_order_type', // this permission make the user ignores {view_history_customer_order_type / view_history_technical_support_order_type / view_history_high_technical_support_order_type / view_history_external_technical_support_order_type}
            'view_history_customer_order_type',
            'view_history_technical_support_order_type',
            'view_history_high_technical_support_order_type',
            'view_history_external_technical_support_order_type',

            'view_archived_count',
            'view_canceled_count',
            'view_closed_count',
            'view_owned_count',
            'view_escalated_to_high_technical_support_count',
            'view_escalated_to_external_technical_support_count',
            'view_opened_count',
            'view_total_count',
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema(function (Page $livewire) {
                if ($livewire instanceof CreateTicket) {
                    return Self::getCreateForm();
                }
                if ($livewire instanceof EditTicket) {
                    return Self::getEditForm();
                }
                if ($livewire instanceof ViewTicket) {
                    return Self::getViewForm();
                }
            })
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('10s')
            ->modifyQueryUsing(function (Builder $query) {
                // NOTE: this is for admins
                if (auth()->user()->can('can_view_all_ticket')) {
                    return $query
                        ->orderByDesc('id', 'des');
                }
                if (auth()->user()->level_id == 1 || auth()->user()->level_id == 2 || auth()->user()->level_id == 3 || auth()->user()->level_id == 4) {
                    if (auth()->user()->can('can_ignore_level_when_view_ticket')) {
                        if (auth()->user()->can('can_ignore_department_when_view_ticket')) {
                            // NOTE: this is for managers
                            return $query
                                ->whereNull('deleted_at')
                                ->orderByDesc('id', 'des');
                        } else {
                            // NOTE: this is for customers
                            return $query
                                ->where('department_id', auth()->user()->department_id)
                                ->whereNull('deleted_at')
                                ->orderByDesc('id', 'des');
                        }
                    } else {
                        // NOTE: this is for support l1 / support l1 manager / support l2 / support l2 manager / etc..
                        if (auth()->user()->can('can_ignore_department_when_view_ticket')) {
                            return $query
                                ->where('level_id', auth()->user()->level_id)
                                ->whereNull('deleted_at')
                                ->orderByDesc('id', 'des');
                        } else {
                            return $query
                                ->where('department_id', auth()->user()->department_id)
                                ->where('level_id', auth()->user()->level_id)
                                ->whereNull('deleted_at')
                                ->orderByDesc('id', 'des');
                        }
                    }
                }
                // NOTE: this is the default query
                return $query
                    ->where('department_id', auth()->user()->department_id)
                    ->where('level_id', auth()->user()->level_id)
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
                        Tables\Columns\TextColumn::make('externalTechnicalSupport.email')
                            ->icon('heroicon-m-users')
                            ->hidden(!(auth()->user()->can('view_external_technical_supports_ticket'))),
                    ]),
                    Stack::make([
                        Tables\Columns\TextColumn::make('created_at')
                            ->formatStateUsing(function ($state) {
                                return 'Created At: ' . $state;
                            })
                            ->badge()
                            ->color('gray')
                            ->hidden(!(auth()->user()->can('view_created_at_ticket'))),
                        Tables\Columns\TextColumn::make('escalated_to_high_technical_support_at')
                            ->formatStateUsing(function ($state) {
                                return 'Escalated To SL2 At: ' . $state;
                            })
                            ->badge()
                            ->color('gray')
                            ->hidden(!(auth()->user()->can('view_escalated_to_high_technical_support_at_ticket'))),
                        Tables\Columns\TextColumn::make('escalated_to_external_technical_support_at')
                            ->formatStateUsing(function ($state) {
                                return 'Escalated To SL3 At: ' . $state;
                            })
                            ->badge()
                            ->color('gray')
                            ->hidden(!(auth()->user()->can('view_escalated_to_external_technical_support_at_ticket'))),
                        Tables\Columns\TextColumn::make('start_at')
                            ->formatStateUsing(function ($state) {
                                return 'Started At: ' . $state;
                            })
                            ->badge()
                            ->color('gray')
                            ->hidden(!(auth()->user()->can('view_start_at_ticket'))),
                        Tables\Columns\TextColumn::make('end_at')
                            ->formatStateUsing(function ($state) {
                                return 'Closed At: ' . $state;
                            })
                            ->badge()
                            ->color('gray')
                            ->hidden(!(auth()->user()->can('view_end_at_ticket'))),
                        Tables\Columns\TextColumn::make('canceled_at')
                            ->formatStateUsing(function ($state) {
                                return 'Canceled At: ' . $state;
                            })
                            ->badge()
                            ->color('danger'),
                        Tables\Columns\TextColumn::make('deleted_at')
                            ->formatStateUsing(function ($state) {
                                return 'Archived At: ' . $state;
                            })
                            ->hidden(!(auth()->user()->can('view_archived_at_ticket')))
                            ->badge()
                            ->color('primary'),
                    ]),
                    Split::make([
                        Stack::make([
                            Tables\Columns\TextColumn::make('department.title')
                                ->icon('heroicon-m-building-office'),
                            Tables\Columns\TextColumn::make('category.title')
                                ->icon('heroicon-m-cog-8-tooth'),
                        ]),
                        Stack::make([
                            Tables\Columns\TextColumn::make('priority.title')
                                ->icon('heroicon-m-exclamation-circle'),
                            Tables\Columns\TextColumn::make('type.title')
                                ->icon('heroicon-m-hashtag'),
                        ]),
                    ]),
                    Stack::make([
                        Tables\Columns\TextColumn::make('status')
                            ->formatStateUsing(function ($state) {
                                return 'Current Status: ' . $state;
                            })
                            ->hidden(!(auth()->user()->can('view_status_ticket'))),
                        Tables\Columns\TextColumn::make('handler')
                            ->formatStateUsing(function ($state) {
                                return 'Current Handler: ' . $state;
                            })
                            ->hidden(!(auth()->user()->can('view_handler_ticket'))),
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
                TernaryFilter::make('canceled_at')
                    ->hidden(!(auth()->user()->can('can_filter_table_ticket')))
                    ->label('Is Canceled')
                    ->placeholder('Is Canceled')
                    ->trueLabel('Yes')
                    ->falseLabel('No')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('canceled_at'),
                        false: fn (Builder $query) => $query->whereNull('canceled_at'),
                        blank: fn (Builder $query) => $query,
                    ),
                SelectFilter::make('handler')
                    ->hidden(!(auth()->user()->can('can_filter_table_ticket')))
                    ->options([
                        TicketHandler::CUSTOMER->value => 'Customer',
                        TicketHandler::TECHNICAL_SUPPORT->value => 'SL1',
                        TicketHandler::HIGH_TECHNICAL_SUPPORT->value => 'SL2',
                    ]),
                SelectFilter::make('status')
                    ->hidden(!(auth()->user()->can('can_filter_table_ticket')))
                    ->options([
                        TicketStatus::IN_PROGRESS->value => 'In Progress',
                        TicketStatus::CUSTOMER_PENDING->value => 'Customer Pending',
                        TicketStatus::CUSTOMER_UNDER_MONITORING->value => 'Customer Under Monitoring',
                        TicketStatus::CLOSED->value => 'Closed',
                        TicketStatus::HIGH_TECHNICAL_SUPPORT_PENDING->value => 'SL2 Pending',
                        TicketStatus::TECHNICAL_SUPPORT_PENDING->value => 'SL1 Pending',
                        TicketStatus::TECHNICAL_SUPPORT_UNDER_MONITORING->value => 'SL1 Under Monitoring',
                    ]),
                SelectFilter::make('level_id')
                    ->label('Level')
                    ->hidden(!(auth()->user()->can('can_filter_table_ticket')))
                    ->options([
                        2 => 'level 1',
                        3 => 'level 2',
                        4 => 'level 3',
                    ]),
                SelectFilter::make('department_id')
                    ->label('Department')
                    ->hidden(!(auth()->user()->can('can_filter_table_ticket')))
                    ->options(
                        Department::where('title', '!=', 'default')->pluck('title', 'id')
                    ),
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
            ->filtersFormColumns(3)
            ->actions([
                Tables\Actions\ActionGroup::make([

                    // NOTE: default actions
                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\EditAction::make()
                        ->label('Access'),
                    Tables\Actions\ViewAction::make()
                        ->label('View Details'),

                    Tables\Actions\ActionGroup::make([

                        // NOTE: export_ticket action
                        Tables\Actions\Action::make('export_ticket')
                            ->label('Export PDF')
                            ->icon('heroicon-m-arrow-down-circle')
                            ->color('success')
                            ->hidden(!(auth()->user()->can('can_export_pdf_ticket')))
                            ->action(function (Ticket $record): void {
                                redirect('/tickets/' . $record->id . '/pdf');
                            }),

                        // NOTE: export_ticket action
                        Tables\Actions\Action::make('download_all_files')
                            ->label('Download All Files')
                            ->icon('heroicon-m-arrow-down-on-square-stack')
                            ->color('primary')
                            ->hidden(!(auth()->user()->can('can_download_all_files_ticket')))
                            ->action(function (Ticket $record): void {
                                redirect('/tickets/' . $record->id . '/files/redirect');
                            }),
                    ])
                        ->label('Download')
                        ->grouped(),

                    // NOTE: activate_ticket action
                    Tables\Actions\Action::make('activate_ticket')
                        ->icon('heroicon-m-check-badge')
                        ->color('success')
                        ->hidden(!(auth()->user()->can('can_activate_ticket')))
                        ->visible(function (Ticket $record) {
                            if ($record->status == TicketStatus::CLOSED->value) {
                                return false;
                            }
                            if (!is_null($record->deleted_at)) {
                                return false;
                            }
                            if (!is_null($record->canceled_at)) {
                                return true;
                            }
                            return false;
                        })
                        ->requiresConfirmation()
                        ->action(function (Ticket $record): void {
                            DB::transaction(function () use ($record) {
                                $record->canceled_at = null;
                                $ticketHistory = new TicketHistory([
                                    'ticket_id' => $record->id,
                                    'title' => 'Ticket has been activated',
                                    'owner' => auth()->user()->email,
                                    'work_order' => null,
                                    'sub_work_order' => null,
                                    'status' => $record->status,
                                    'handler' => $record->handler,
                                    'created_at' => now(),
                                ]);
                                $record->ticketHistory()->save($ticketHistory);
                                $record->save();
                                Notification::make()
                                    ->title('Ticket has been activated')
                                    ->success()
                                    ->send();
                            });
                        }),

                    // NOTE: archive_ticket action
                    Tables\Actions\Action::make('archive_ticket')
                        ->icon('heroicon-m-archive-box-arrow-down')
                        ->color('primary')
                        ->hidden(!(auth()->user()->can('can_archive_ticket')))
                        ->requiresConfirmation()
                        ->visible(function (Ticket $record) {
                            return is_null($record->deleted_at) && ($record->status == TicketStatus::CLOSED->value || !is_null($record->canceled_at));
                        })
                        ->action(function (Ticket $record): void {
                            DB::transaction(function () use ($record) {
                                $record->deleted_at = now();
                                $ticketHistory = new TicketHistory([
                                    'ticket_id' => $record->id,
                                    'title' => 'Ticket has been archived',
                                    'owner' => auth()->user()->email,
                                    'work_order' => null,
                                    'sub_work_order' => null,
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

                    // NOTE: assign action
                    Tables\Actions\Action::make('assign')
                        ->requiresConfirmation()
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(function ($record) {
                            if (TicketResource::isTicketEnabled($record)) {
                                if (auth()->user()->can('can_not_self_assign_ticket')) {
                                    return false;
                                }
                                if (
                                    $record->customer->contains(auth()->user()->id) ||
                                    $record->technicalSupport->contains(auth()->user()->id) ||
                                    $record->HighTechnicalSupport->contains(auth()->user()->id) ||
                                    $record->externalTechnicalSupport->contains(auth()->user()->id)
                                ) {
                                    return false;
                                } else {
                                    return true;
                                }
                            } else {
                                return false;
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
                                    if (auth()->user()->level_id == 4) {
                                        $record->externalTechnicalSupport()->attach(auth()->user()->id);
                                    }
                                    $ticketHistory = new TicketHistory([
                                        'ticket_id' => $record->id,
                                        'title' => 'Ticket has been assigned to: ' . auth()->user()->email,
                                        'owner' => auth()->user()->email,
                                        'work_order' => null,
                                        'sub_work_order' => null,
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
                                    ->title('Ticket assigned to you')
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
                    ->button()
                    ->label('Actions'),
            ])
            ->bulkActions([
                ExportBulkAction::make()
                    ->hidden(!(auth()->user()->can('can_export_excel_ticket')))
                    ->exports([
                        ExcelExport::make()
                            ->withColumns([
                                Column::make('ticket_identifier'),
                                Column::make('department')
                                    ->getStateUsing(function ($record) {
                                        return Department::where('id', $record->department_id)->first()->title;
                                    }),
                                Column::make('company'),
                                Column::make('owner')
                                    ->getStateUsing(function ($record) {
                                        return $record->customer()->where('owner', 1)->first()->email ?? 'No email found';
                                    }),
                                Column::make('ne_product'),
                                Column::make('sw_version'),
                                Column::make('type')
                                    ->getStateUsing(function ($record) {
                                        return Type::where('id', $record->type_id)->first()->title;
                                    }),
                                Column::make('priority')
                                    ->getStateUsing(function ($record) {
                                        return Priority::where('id', $record->priority_id)->first()->title;
                                    }),
                                Column::make('start_at'),
                                Column::make('end_at'),
                                Column::make('description'),
                                Column::make('status'),
                                Column::make('handler'),
                                Column::make('update')
                                    ->getStateUsing(function ($record) {
                                        return $record->ticketHistory->last()->title ?? 'No Update';
                                    }),
                                Column::make('last_action')
                                    ->getStateUsing(function ($record) {
                                        $ticket = $record->ticketHistory->whereNotNull('work_order');
                                        if ($ticket->count() > 0) {
                                            if (is_null($ticket->last()?->sub_work_order)) {
                                                return TicketHistoryRelationManager::formatTitleUsing(
                                                    $ticket->last()->work_order
                                                );
                                            } else {
                                                return TicketHistoryRelationManager::formatTitleUsing(
                                                    $ticket->last()->work_order . ' - ' . $ticket->last()->sub_work_order
                                                );
                                            }
                                        } else {
                                            return 'No Action';
                                        }
                                    }),
                                Column::make('escalated_to_high_technical_support_at'),
                                Column::make('troubleshooting_activity_for_customer')
                                    ->getStateUsing(function ($record) {
                                        return $record->ticketHistory->where(
                                            'work_order',
                                            TicketWorkOrder::CUSTOMER_TROUBLESHOOTING_ACTIVITY->value
                                        )->first()?->created_at ?? 'No Date';
                                    }),
                                Column::make('troubleshooting_activity_for_technical_support')
                                    ->getStateUsing(function ($record) {
                                        return $record->ticketHistory->where(
                                            'work_order',
                                            TicketWorkOrder::TECHNICAL_SUPPORT_TROUBLESHOOTING_ACTIVITY->value
                                        )->first()?->created_at ?? 'No Date';
                                    }),
                                Column::make('workaround_solution_for_customer_provided')
                                    ->getStateUsing(function ($record) {
                                        if ($record->ticketHistory->where(
                                            'work_order',
                                            TicketWorkOrder::WORKAROUND_ACCEPTED_BY_CUSTOMER->value
                                        )->count() > 0) {
                                            return 'Yes';
                                        } else {
                                            return 'No';
                                        }
                                    }),
                                Column::make('workaround_solution_duration')
                                    ->getStateUsing(function ($record) {
                                        if (!is_null($record->ticketHistory->where('work_order', TicketWorkOrder::WORKAROUND_ACCEPTED_BY_CUSTOMER->value)->first()?->created_at)) {
                                            return Carbon::create($record->start_at)->diffInDays(Carbon::create($record->ticketHistory->where('work_order', TicketWorkOrder::WORKAROUND_ACCEPTED_BY_CUSTOMER->value)->first()?->created_at));
                                        } else {
                                            return 'no workaround solution duration';
                                        }
                                    }),
                                // Column::make('duration_for_fixing_in_days_so_far_even_not_closed_indicate_days_so_far')
                                //     ->getStateUsing(function ($record) {
                                //         if ($record->ticketHistory->where('work_order', TicketWorkOrder::RESOLUTION_ACCEPTED_BY_CUSTOMER->value)->count() > 0) {
                                //             return Carbon::create($record->ticketHistory->where('work_order', TicketWorkOrder::RESOLUTION_ACCEPTED_BY_CUSTOMER->value)->last()->created_at)->diffInDays(Carbon::create($record->ticketHistory->where('work_order', TicketWorkOrder::CUSTOMER_TROUBLESHOOTING_ACTIVITY->value)->last()->created_at));
                                //         } else {
                                //             return 'NON';
                                //         }
                                //     }),
                                Column::make('final_solution_provided')
                                    ->getStateUsing(function ($record) {
                                        if ($record->ticketHistory->where('work_order', TicketWorkOrder::RESOLUTION_ACCEPTED_BY_CUSTOMER->value)->count() > 0) {
                                            return 'Yes';
                                        } else {
                                            return 'No';
                                        }
                                    }),
                                Column::make('final_solution_duration')
                                    ->getStateUsing(function ($record) {
                                        if (!is_null($record->end_at)) {
                                            return Carbon::create($record->start_at)->diffInDays($record->end_at);
                                        } else {
                                            return 'no final solution duration';
                                        }
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
                    Forms\Components\Section::make('Ticket Data')
                        ->schema([
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
                        ])
                        ->columnSpan(4)
                        ->columns(3),
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
                ->columns(3),
        ];
    }

    private static function getEditForm(): array
    {
        return [
            Forms\Components\TextInput::make('deleted_at')
                ->hidden(!(auth()->user()->can('view_archived_at_ticket')))
                ->visible(function ($record) {
                    return !is_null($record->deleted_at);
                })
                ->columnSpan(3)
                ->label('Archived At')
                ->disabled(true)
                ->dehydrated(true),
            Forms\Components\TextInput::make('canceled_at')
                ->visible(function ($record) {
                    return !is_null($record->canceled_at);
                })
                ->columnSpan(3)
                ->label('Canceled At')
                ->disabled(true)
                ->dehydrated(true),
            Forms\Components\TextInput::make('escalated_to_high_technical_support_at')
                ->hidden(!(auth()->user()->can('view_escalated_to_high_technical_support_at_ticket')))
                ->visible(function ($record) {
                    return !is_null($record->escalated_to_high_technical_support_at);
                })
                ->columnSpan(3)
                ->label('Escalated To SL2 At')
                ->disabled(true)
                ->dehydrated(true),
            Forms\Components\TextInput::make('escalated_to_external_technical_support_at')
                ->hidden(!(auth()->user()->can('view_escalated_to_external_technical_support_at_ticket')))
                ->visible(function ($record) {
                    return !is_null($record->escalated_to_external_technical_support_at);
                })
                ->columnSpan(3)
                ->label('Escalated To SL3 At')
                ->disabled(true)
                ->dehydrated(true),
            Forms\Components\Section::make('Ticket Info')
                ->schema([
                    Forms\Components\Section::make('Ticket Data')
                        ->schema([
                            Forms\Components\TextInput::make('ticket_identifier')
                                ->disabled(true)
                                ->dehydrated(true),
                            Forms\Components\TextInput::make('title')
                                ->disabled(
                                    function ($record) {
                                        return self::getDisableEditFormCondition($record);
                                    }
                                )
                                ->dehydrated(true)
                                ->required()
                                ->maxLength(64),
                            Forms\Components\TextInput::make('ne_product')
                                ->disabled(
                                    function ($record) {
                                        return self::getDisableEditFormCondition($record);
                                    }
                                )
                                ->dehydrated(true)
                                ->required()
                                ->maxLength(64),
                            Forms\Components\TextInput::make('sw_version')
                                ->disabled(
                                    function ($record) {
                                        return self::getDisableEditFormCondition($record);
                                    }
                                )
                                ->dehydrated(true)
                                ->required()
                                ->maxLength(64),
                            Forms\Components\TextInput::make('company')
                                ->disabled(
                                    function ($record) {
                                        return self::getDisableEditFormCondition($record);
                                    }
                                )
                                ->dehydrated(true)
                                ->columnSpanFull(),
                            Forms\Components\Textarea::make('description')
                                ->disabled(
                                    function ($record) {
                                        return self::getDisableEditFormCondition($record);
                                    }
                                )
                                ->dehydrated(true)
                                ->required()
                                ->columnSpanFull()
                                ->maxLength(512),
                        ])
                        ->columnSpan(4)
                        ->columns(4),
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
                        ->columns(2),
                    Forms\Components\Section::make('Ticket Files')
                        ->schema([
                            Forms\Components\FileUpload::make('attachments')
                                ->downloadable()
                                ->disabled(true)
                                ->dehydrated(true)
                                ->columnSpanFull()
                                ->multiple(),
                        ])
                        ->columnSpan(4)
                        ->columns(3),
                ])
                ->columnSpan(2)
                ->columns(4),
            Forms\Components\Section::make('Ticket Sub Info')
                ->disabled()
                ->schema([
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
                        ->columnSpan(1)
                        ->columns(1),
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
                        ->columnSpan(1)
                        ->columns(1),
                    Forms\Components\Section::make('Ticket Users')
                        ->schema([
                            Forms\Components\Select::make('customer')
                                ->hidden(!(auth()->user()->can('view_customers_ticket')))
                                ->multiple()
                                ->relationship('customer', 'email')
                                ->disabled(true)
                                ->dehydrated(true),
                            Forms\Components\Select::make('technical_support')
                                ->label('SL1')
                                ->hidden(!(auth()->user()->can('view_technical_supports_ticket')))
                                ->multiple()
                                ->relationship('technicalSupport', 'email')
                                ->disabled(true)
                                ->dehydrated(true),
                            Forms\Components\Select::make('high_technical_support')
                                ->label('SL2')
                                ->hidden(!(auth()->user()->can('view_high_technical_supports_ticket')))
                                ->multiple()
                                ->relationship('highTechnicalSupport', 'email')
                                ->disabled(true)
                                ->dehydrated(true),
                            Forms\Components\Select::make('external_technical_support')
                                ->label('SL3')
                                ->hidden(!(auth()->user()->can('view_external_technical_supports_ticket')))
                                ->multiple()
                                ->relationship('externalTechnicalSupport', 'email')
                                ->disabled(true)
                                ->dehydrated(true),
                        ])
                        ->columnSpan(1)
                        ->columns(1),
                ])
                ->columnSpan(1)
                ->columns(1),
        ];
    }

    private static function getViewForm(): array
    {
        return [
            Forms\Components\TextInput::make('deleted_at')
                ->hidden(!(auth()->user()->can('view_archived_at_ticket')))
                ->visible(function ($record) {
                    return !is_null($record->deleted_at);
                })
                ->columnSpan(3)
                ->label('Archived At'),
            Forms\Components\TextInput::make('canceled_at')
                ->visible(function ($record) {
                    return !is_null($record->canceled_at);
                })
                ->columnSpan(3)
                ->label('Canceled At'),
            Forms\Components\TextInput::make('escalated_to_high_technical_support_at')
                ->hidden(!(auth()->user()->can('view_escalated_to_high_technical_support_at_ticket')))
                ->visible(function ($record) {
                    return !is_null($record->escalated_to_high_technical_support_at);
                })
                ->columnSpan(3)
                ->label('Escalated To SL2 At'),
            Forms\Components\TextInput::make('escalated_to_external_technical_support_at')
                ->hidden(!(auth()->user()->can('view_escalated_to_external_technical_support_at_ticket')))
                ->visible(function ($record) {
                    return !is_null($record->escalated_to_external_technical_support_at);
                })
                ->columnSpan(3)
                ->label('Escalated To SL3 At'),
            Forms\Components\Section::make('Ticket Info')
                ->schema([
                    Forms\Components\Section::make('Ticket Data')
                        ->schema([
                            Forms\Components\TextInput::make('ticket_identifier'),
                            Forms\Components\TextInput::make('title'),
                            Forms\Components\TextInput::make('ne_product'),
                            Forms\Components\TextInput::make('sw_version'),
                            Forms\Components\TextInput::make('company')
                                ->columnSpanFull(),
                            Forms\Components\Textarea::make('description')
                                ->columnSpanFull(),
                        ])
                        ->columnSpan(4)
                        ->columns(4),
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
                                ->columnSpanFull(),
                        ])
                        ->columnSpan(4)
                        ->columns(3),
                ])
                ->columnSpan(2)
                ->columns(4),
            Forms\Components\Section::make('Ticket Sub Info')
                ->schema([
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
                        ->columnSpan(1)
                        ->columns(1),
                    Forms\Components\Section::make('Ticket Proccess Time')
                        ->schema([
                            Forms\Components\DateTimePicker::make('start_at')
                                ->hidden(!(auth()->user()->can('view_start_at_ticket'))),
                            Forms\Components\DateTimePicker::make('end_at')
                                ->hidden(!(auth()->user()->can('view_end_at_ticket'))),
                        ])
                        ->columnSpan(1)
                        ->columns(1),
                    Forms\Components\Section::make('Ticket Users')
                        ->schema([
                            Forms\Components\Select::make('customer')
                                ->hidden(!(auth()->user()->can('view_customers_ticket')))
                                ->multiple()
                                ->relationship('customer', 'email'),
                            Forms\Components\Select::make('technical_support')
                                ->label('SL1')
                                ->hidden(!(auth()->user()->can('view_technical_supports_ticket')))
                                ->multiple()
                                ->relationship('technicalSupport', 'email'),
                            Forms\Components\Select::make('high_technical_support')
                                ->label('SL2')
                                ->hidden(!(auth()->user()->can('view_high_technical_supports_ticket')))
                                ->multiple()
                                ->relationship('externalTechnicalSupport', 'email'),
                            Forms\Components\Select::make('external_technical_support')
                                ->label('SL3')
                                ->hidden(!(auth()->user()->can('view_external_technical_supports_ticket')))
                                ->multiple()
                                ->relationship('externalTechnicalSupport', 'email'),
                        ])
                        ->columnSpan(1)
                        ->columns(1),
                ])
                ->columnSpan(1)
                ->columns(1),
        ];
    }

    public static function isTicketEnabled($record): bool
    {
        if ($record->status == TicketStatus::CLOSED->value) {
            return false;
        }
        if (!is_null($record->deleted_at)) {
            return false;
        }
        if (!is_null($record->canceled_at)) {
            return false;
        }
        return true;
    }

    private static function getDisableEditFormCondition($record): bool
    {
        if (auth()->user()->can('can_edit_any_info_ticket')) {
            return false;
        } else {
            if (
                $record->technicalSupport->count() > 0 ||
                $record->highTechnicalSupport->count() > 0 ||
                $record->externalTechnicalSupport->count() > 0
            ) {
                return true && self::isTicketEnabled($record);
            } else {
                return is_null($record->customer()->where('id', auth()->user()->id)->where('owner', 1)->first()) && self::isTicketEnabled($record);
            }
        }
    }

    private static function getTicketOrderType($record): array
    {
        if (auth()->user()->can('view_all_order_type_ticket')) {
            return [
                TicketWorkOrder::FEEDBACK_TO_CUSTOMER->value => 'Feedback to Customer',
                TicketWorkOrder::CUSTOMER_TROUBLESHOOTING_ACTIVITY->value => 'Customer Troubleshooting Activity',
                TicketWorkOrder::CUSTOMER_RESPONSE->value => 'Customer Response',
                TicketWorkOrder::WORKAROUND_ACCEPTED_BY_CUSTOMER->value => 'Workaround Accepted By Customer',
                TicketWorkOrder::RESOLUTION_ACCEPTED_BY_CUSTOMER->value => 'Resolution Accepted by Customer',

                TicketWorkOrder::FEEDBACK_TO_TECHNICAL_SUPPORT->value => 'Feedback to SL1',
                TicketWorkOrder::TECHNICAL_SUPPORT_TROUBLESHOOTING_ACTIVITY->value => 'SL1 Troubleshooting Activity',
                TicketWorkOrder::TECHNICAL_SUPPORT_RESPONSE->value => 'SL1 Response',
                TicketWorkOrder::WORKAROUND_ACCEPTED_BY_TECHNICAL_SUPPORT->value => 'Workaround Accepted by SL1',
                TicketWorkOrder::RESOLUTION_ACCEPTED_BY_TECHNICAL_SUPPORT->value => 'Resolution Accepted by SL1',

                TicketWorkOrder::FEEDBACK_TO_HIGH_TECHNICAL_SUPPORT->value => 'Feedback to SL2',
                TicketWorkOrder::HIGH_TECHNICAL_SUPPORT_TROUBLESHOOTING_ACTIVITY->value => 'SL2 Troubleshooting Activity',
                TicketWorkOrder::HIGH_TECHNICAL_SUPPORT_RESPONSE->value => 'SL2 Response',
                TicketWorkOrder::WORKAROUND_ACCEPTED_BY_HIGH_TECHNICAL_SUPPORT->value => 'Workaround Accepted by SL2',
                TicketWorkOrder::RESOLUTION_ACCEPTED_BY_HIGH_TECHNICAL_SUPPORT->value => 'Resolution Accepted by SL2',
            ];
        }
        if (auth()->user()->can('view_customer_order_type_ticket')) {
            return [
                TicketWorkOrder::FEEDBACK_TO_CUSTOMER->value => 'Feedback to Customer',
                TicketWorkOrder::CUSTOMER_TROUBLESHOOTING_ACTIVITY->value => 'Customer Troubleshooting Activity',
                TicketWorkOrder::CUSTOMER_RESPONSE->value => 'Customer Response',
                TicketWorkOrder::WORKAROUND_ACCEPTED_BY_CUSTOMER->value => 'Workaround Accepted By Customer',
                TicketWorkOrder::RESOLUTION_ACCEPTED_BY_CUSTOMER->value => 'Resolution Accepted by Customer',
            ];
        }
        if (auth()->user()->can('view_technical_support_order_type_ticket')) {
            return [
                TicketWorkOrder::FEEDBACK_TO_CUSTOMER->value => 'Feedback to Customer',
                TicketWorkOrder::CUSTOMER_TROUBLESHOOTING_ACTIVITY->value => 'Customer Troubleshooting Activity',
                TicketWorkOrder::CUSTOMER_RESPONSE->value => 'Customer Response',
                TicketWorkOrder::WORKAROUND_ACCEPTED_BY_CUSTOMER->value => 'Workaround Accepted By Customer',
                TicketWorkOrder::RESOLUTION_ACCEPTED_BY_CUSTOMER->value => 'Resolution Accepted by Customer',

                TicketWorkOrder::FEEDBACK_TO_TECHNICAL_SUPPORT->value => 'Feedback to SL1',
                TicketWorkOrder::TECHNICAL_SUPPORT_TROUBLESHOOTING_ACTIVITY->value => 'SL1 Troubleshooting Activity',
                TicketWorkOrder::TECHNICAL_SUPPORT_RESPONSE->value => 'SL1 Response',
                TicketWorkOrder::WORKAROUND_ACCEPTED_BY_TECHNICAL_SUPPORT->value => 'Workaround Accepted by SL1',
                TicketWorkOrder::RESOLUTION_ACCEPTED_BY_TECHNICAL_SUPPORT->value => 'Resolution Accepted by SL1',
            ];
        }
        if (auth()->user()->can('view_high_technical_support_order_type_ticket')) {
            return [
                TicketWorkOrder::FEEDBACK_TO_TECHNICAL_SUPPORT->value => 'Feedback to SL1',
                TicketWorkOrder::TECHNICAL_SUPPORT_TROUBLESHOOTING_ACTIVITY->value => 'SL1 Troubleshooting Activity',
                TicketWorkOrder::TECHNICAL_SUPPORT_RESPONSE->value => 'SL1 Response',
                TicketWorkOrder::WORKAROUND_ACCEPTED_BY_TECHNICAL_SUPPORT->value => 'Workaround Accepted by SL1',
                TicketWorkOrder::RESOLUTION_ACCEPTED_BY_TECHNICAL_SUPPORT->value => 'Resolution Accepted by SL1',

                TicketWorkOrder::FEEDBACK_TO_HIGH_TECHNICAL_SUPPORT->value => 'Feedback to SL2',
                TicketWorkOrder::HIGH_TECHNICAL_SUPPORT_TROUBLESHOOTING_ACTIVITY->value => 'SL2 Troubleshooting Activity',
                TicketWorkOrder::HIGH_TECHNICAL_SUPPORT_RESPONSE->value => 'SL2 Response',
                TicketWorkOrder::WORKAROUND_ACCEPTED_BY_HIGH_TECHNICAL_SUPPORT->value => 'Workaround Accepted by SL2',
                TicketWorkOrder::RESOLUTION_ACCEPTED_BY_HIGH_TECHNICAL_SUPPORT->value => 'Resolution Accepted by SL2',
            ];
        }
        if (auth()->user()->can('view_external_technical_support_order_type_ticket')) {
            return [
                TicketWorkOrder::FEEDBACK_TO_HIGH_TECHNICAL_SUPPORT->value => 'Feedback to SL2',
                TicketWorkOrder::HIGH_TECHNICAL_SUPPORT_TROUBLESHOOTING_ACTIVITY->value => 'SL2 Troubleshooting Activity',
                TicketWorkOrder::HIGH_TECHNICAL_SUPPORT_RESPONSE->value => 'SL2 Response',
                TicketWorkOrder::WORKAROUND_ACCEPTED_BY_HIGH_TECHNICAL_SUPPORT->value => 'Workaround Accepted by SL2',
                TicketWorkOrder::RESOLUTION_ACCEPTED_BY_HIGH_TECHNICAL_SUPPORT->value => 'Resolution Accepted by SL2',
            ];
        }
        return [];
    }

    private static function getTicketSubOrderType($record): array
    {
        if (auth()->user()->can('view_all_order_type_ticket')) {
            return [
                TicketSubWorkOrder::CUSTOMER_INFORMATION_REQUIRED->value => 'Customer Information Required',
                TicketSubWorkOrder::WORKAROUND_CUSTOMER_INFORMATION->value => 'Workaround Customer Information',
                TicketSubWorkOrder::FINAL_CUSTOMER_INFORMATION->value => 'Final Customer Information',

                TicketSubWorkOrder::TECHNICAL_SUPPORT_INFORMATION_REQUIRED->value => 'SL1 Information Required',
                TicketSubWorkOrder::WORKAROUND_TECHNICAL_SUPPORT_INFORMATION->value => 'Workaround SL1 Information',
                TicketSubWorkOrder::FINAL_TECHNICAL_SUPPORT_INFORMATION->value => 'Final SL1 Information',

                TicketSubWorkOrder::HIGH_TECHNICAL_SUPPORT_INFORMATION_REQUIRED->value => 'SL2 Information Required',
                TicketSubWorkOrder::WORKAROUND_HIGH_TECHNICAL_SUPPORT_INFORMATION->value => 'Workaround SL2 Information',
                TicketSubWorkOrder::FINAL_HIGH_TECHNICAL_SUPPORT_INFORMATION->value => 'Final SL2 Information',
            ];
        }
        if (auth()->user()->can('view_customer_order_type_ticket')) {
            return [
                TicketSubWorkOrder::CUSTOMER_INFORMATION_REQUIRED->value => 'Customer Information Required',
                TicketSubWorkOrder::WORKAROUND_CUSTOMER_INFORMATION->value => 'Workaround Customer Information',
                TicketSubWorkOrder::FINAL_CUSTOMER_INFORMATION->value => 'Final Customer Information',
            ];
        }
        if (auth()->user()->can('view_technical_support_order_type_ticket')) {
            return [
                TicketSubWorkOrder::CUSTOMER_INFORMATION_REQUIRED->value => 'Customer Information Required',
                TicketSubWorkOrder::WORKAROUND_CUSTOMER_INFORMATION->value => 'Workaround Customer Information',
                TicketSubWorkOrder::FINAL_CUSTOMER_INFORMATION->value => 'Final Customer Information',

                TicketSubWorkOrder::TECHNICAL_SUPPORT_INFORMATION_REQUIRED->value => 'SL1 Information Required',
                TicketSubWorkOrder::WORKAROUND_TECHNICAL_SUPPORT_INFORMATION->value => 'Workaround SL1 Information',
                TicketSubWorkOrder::FINAL_TECHNICAL_SUPPORT_INFORMATION->value => 'Final SL1 Information',
            ];
        }
        if (auth()->user()->can('view_high_technical_support_order_type_ticket')) {
            return [
                TicketSubWorkOrder::TECHNICAL_SUPPORT_INFORMATION_REQUIRED->value => 'SL1 Information Required',
                TicketSubWorkOrder::WORKAROUND_TECHNICAL_SUPPORT_INFORMATION->value => 'Workaround SL1 Information',
                TicketSubWorkOrder::FINAL_TECHNICAL_SUPPORT_INFORMATION->value => 'Final SL1 Information',

                TicketSubWorkOrder::HIGH_TECHNICAL_SUPPORT_INFORMATION_REQUIRED->value => 'SL2 Information Required',
                TicketSubWorkOrder::WORKAROUND_HIGH_TECHNICAL_SUPPORT_INFORMATION->value => 'Workaround SL2 Information',
                TicketSubWorkOrder::FINAL_HIGH_TECHNICAL_SUPPORT_INFORMATION->value => 'Final SL2 Information',
            ];
        }
        if (auth()->user()->can('view_external_technical_support_order_type_ticket')) {
            return [
                TicketSubWorkOrder::HIGH_TECHNICAL_SUPPORT_INFORMATION_REQUIRED->value => 'SL2 Information Required',
                TicketSubWorkOrder::WORKAROUND_HIGH_TECHNICAL_SUPPORT_INFORMATION->value => 'Workaround SL2 Information',
                TicketSubWorkOrder::FINAL_HIGH_TECHNICAL_SUPPORT_INFORMATION->value => 'Final SL2 Information',
            ];
        }
        return [];
    }

    private static function getTicketStatus(): array
    {
        return [
            TicketStatus::IN_PROGRESS->value => 'In Progress',
            TicketStatus::CLOSED->value => 'Closed',
            TicketStatus::CUSTOMER_PENDING->value => 'Customer Pending',
            TicketStatus::CUSTOMER_UNDER_MONITORING->value => 'Customer Under Monitoring',
            TicketStatus::TECHNICAL_SUPPORT_PENDING->value => 'SL1 Pending',
            TicketStatus::TECHNICAL_SUPPORT_UNDER_MONITORING->value => 'SL1 Under Monitoring',
            TicketStatus::HIGH_TECHNICAL_SUPPORT_PENDING->value => 'SL2 Pending',
            TicketStatus::HIGH_TECHNICAL_SUPPORT_UNDER_MONITORING->value => 'SL2 Under Monitoring',
            TicketStatus::EXTERNAL_TECHNICAL_SUPPORT_PENDING->value => 'SL3 Pending',
            TicketStatus::EXTERNAL_TECHNICAL_SUPPORT_UNDER_MONITORING->value => 'SL3 Under Monitoring',
        ];
    }

    private static function getTicketHandler(): array
    {
        return [
            TicketHandler::CUSTOMER->value => 'Customer',
            TicketHandler::TECHNICAL_SUPPORT->value => 'SL1',
            TicketHandler::HIGH_TECHNICAL_SUPPORT->value => 'SL2',
            TicketHandler::EXTERNAL_TECHNICAL_SUPPORT->value => 'SL3',
        ];
    }

    public static function canEdit(Model $record): bool
    {
        // NOTE: enter ticket only if ticket is enabled and assigned or has {can_access_any_ticket} permission
        if (TicketResource::isTicketEnabled($record)) {
            if (
                $record->customer->contains(auth()->user()->id) ||
                $record->technicalSupport->contains(auth()->user()->id) ||
                $record->highTechnicalSupport->contains(auth()->user()->id) ||
                $record->externalTechnicalSupport->contains(auth()->user()->id)
            ) {
                return true;
            }
            if (auth()->user()->can('can_access_any_ticket')) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public static function canView(Model $record): bool
    {
        // NOTE:  view details only if assigned or has {can_view_any_details_ticket} permission
        if (auth()->user()->can('can_view_any_details_ticket')) {
            return true;
        }
        if (TicketResource::isTicketEnabled($record)) {
            if (
                $record->customer->contains(auth()->user()->id) ||
                $record->technicalSupport->contains(auth()->user()->id) ||
                $record->highTechnicalSupport->contains(auth()->user()->id) ||
                $record->externalTechnicalSupport->contains(auth()->user()->id)
            ) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
}
