<?php

namespace App\Filament\Resources\TicketResource\Pages;

use App\Enums\TicketHandler;
use App\Enums\TicketStatus;
use App\Enums\TicketSubWorkOrder;
use App\Enums\TicketWorkOrder;
use App\Exceptions\ControlPanelDomainException;
use App\Filament\Resources\TicketResource;
use App\Mail\TicketEscalation;
use App\Mail\TicketWorkOrder as TicketWorkOrderMail;
use App\Models\Level;
use App\Models\Ticket;
use App\Models\TicketHistory;
use App\Models\User;
use App\Rules\Emails;
use Exception;
use Filament\Actions;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\ActionSize;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class EditTicket extends EditRecord
{
    protected static string $resource = TicketResource::class;

    protected function getHeaderActions(): array
    {
        return [

            //NOTE: manage escalate_ticket
            Actions\Action::make('escalate_ticket')
                ->hidden(!(auth()->user()->can('can_escalate_ticket')))
                ->visible(function (Ticket $record) {
                    if (TicketResource::isTicketEnabled($record)) {
                        if ($record->technicalSupport->count() <= 0) {
                            return false;
                        }
                        return $record->highTechnicalSupport->count() <= 0;
                    } else {
                        return false;
                    }
                })
                ->form([
                    Select::make('user_id')
                        ->label('SL2 User')
                        ->options(function ($record) {
                            return Self::getHighTechnicalSupportUsers($record);
                        })
                        ->required(),
                ])
                ->action(function (array $data, Ticket $record): void {
                    try {
                        DB::transaction(function () use ($data, $record) {

                            $record->highTechnicalSupport()->attach($data['user_id']);
                            $record->level_id = Level::where('code', 2)->first()->id;
                            $record->status = TicketStatus::HIGH_TECHNICAL_SUPPORT_PENDING->value;
                            $record->handler = TicketHandler::HIGH_TECHNICAL_SUPPORT->value;
                            $record->escalated_at = now();
                            $ticketHistory = new TicketHistory([
                                'ticket_id' => $record->id,
                                'title' => 'Ticket has been escalated to: '  . User::where('id', $data['user_id'])->first()->email,
                                'owner' => auth()->user()->email,
                                'work_order' => $record->work_order,
                                'sub_work_order' => $record->sub_work_order,
                                'status' => $record->status,
                                'handler' => $record->handler,
                                'created_at' => now(),
                            ]);
                            $record->ticketHistory()->save($ticketHistory);
                            $record->save();

                            $title = 'Case Escalation:' . ' Case ' . ' # ' . $this->record->ticket_identifier . ' - ' . $this->record->title;

                            $emails = [];
                            $managers = User::whereHas('roles', function ($query) {
                                $query->where('name', 'manager');
                            })->pluck('email')->toArray();
                            $emails = array_merge($emails, $managers);
                            $support = User::where('department_id', $this->record->department_id)->where('level_id', 2)->pluck('email')->toArray();
                            $emails = array_merge($emails, $support);
                            $highSupport = User::where('department_id', $this->record->department_id)->where('level_id', 3)->pluck('email')->toArray();
                            $emails = array_merge($emails, $highSupport);

                            $highSupportManager = User::whereHas('roles', function ($query) {
                                $query->where('name', 'high_support_manager');
                            })->first();

                            if (is_null($highSupportManager)) {
                                throw new ControlPanelDomainException('There is no high support manager');
                            }

                            Mail::to($highSupportManager->email)->send(new TicketEscalation($title, $emails));

                            $this->refreshFormData([
                                'high_technical_support_user_id',
                            ]);
                        });
                        Notification::make()
                            ->title('Ticket has been escalated')
                            ->success()
                            ->send();
                    } catch (ControlPanelDomainException $e) {
                        Notification::make()
                            ->title($e->getMessage())
                            ->warning()
                            ->send();
                    } catch (Exception $e) {
                        if (App::environment('local')) {
                            Notification::make()
                                ->title($e->getMessage())
                                ->danger()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Something went wrong')
                                ->danger()
                                ->send();
                        }
                    }
                }),

            //NOTE: assign_ticket
            Actions\Action::make('assign_ticket')
                ->hidden(!(auth()->user()->can('can_assign_technical_support_ticket')))
                ->visible(function (Ticket $record) {
                    if (TicketResource::isTicketEnabled($record)) {
                        return $record->technicalSupport->count() <= 0;
                    } else {
                        return false;
                    }
                })
                ->form([
                    Select::make('user_id')
                        ->label('SL1 User')
                        ->options(function ($record) {
                            return Self::getTechnicalSupportUsers($record);
                        })
                        ->required(),
                ])
                ->action(function (array $data, Ticket $record): void {
                    DB::transaction(function () use ($data, $record) {
                        $record->technicalSupport()->attach($data['user_id']);
                        $ticketHistory = new TicketHistory([
                            'ticket_id' => $record->id,
                            'title' => 'Ticket has been assigned to: ' . User::where('id', $data['user_id'])->first()->email,
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
                        $this->refreshFormData([
                            'technical_support_user_id',
                            'start_at',
                        ]);
                    });
                    Notification::make()
                        ->title('Ticket has been assigned')
                        ->success()
                        ->send();
                }),

            //NOTE: cancel_ticket
            Actions\Action::make('cancel_ticket')
                ->hidden(!(auth()->user()->can('can_cancel_ticket')))
                ->visible(function (Ticket $record) {
                    return TicketResource::isTicketEnabled($record);
                })
                ->requiresConfirmation()
                ->action(function (Ticket $record): void {
                    DB::transaction(function () use ($record) {
                        $record->canceled_at = now();
                        $ticketHistory = new TicketHistory([
                            'ticket_id' => $record->id,
                            'title' => 'Ticket has been canceled',
                            'owner' => auth()->user()->email,
                            'work_order' => $record->work_order,
                            'sub_work_order' => $record->sub_work_order,
                            'status' => $record->status,
                            'handler' => $record->handler,
                            'created_at' => now(),
                        ]);
                        $record->ticketHistory()->save($ticketHistory);
                        $record->save();
                        $this->refreshFormData([
                            'canceled_at',
                        ]);
                        Notification::make()
                            ->title('Ticket has been canceled')
                            ->success()
                            ->send();
                        return redirect('/admin/tickets/' . $record->id);
                    });
                }),

            //NOTE: create work order type
            Actions\Action::make('create_work_order')
                ->hidden(!(auth()->user()->can('create_work_order_type_ticket')))
                ->visible(function (Ticket $record) {
                    if (TicketResource::isTicketEnabled($record)) {
                        return $record->technicalSupport->count() > 0;
                    } else {
                        return false;
                    }
                })
                ->form(
                    Self::getCreateOrderTypeForm()
                )
                ->action(function (array $data, Ticket $record) {
                    Self::createOrderType($data, $record);
                    $this->refreshFormData([
                        'work_order',
                        'sub_work_order',
                        'status',
                        'handler',
                    ]);
                }),

            //NOTE: manage user_ticket
            ActionGroup::make([
                Actions\Action::make('add_technical_support')
                    ->label('Add SL1')
                    ->hidden(!(auth()->user()->can('add_technical_support_ticket')))
                    ->visible(function (Ticket $record) {
                        if (TicketResource::isTicketEnabled($record)) {
                            return $record->technicalSupport->count() >= 1;
                        } else {
                            return false;
                        }
                    })
                    ->requiresConfirmation()
                    ->form([
                        Select::make('user_id')
                            ->label('SL1 User')
                            ->options(function ($record) {
                                return Self::getTechnicalSupportUsers($record);
                            })
                            ->required(),
                    ])
                    ->action(function (array $data, Ticket $record): void {
                        try {
                            DB::transaction(function () use ($data, $record) {
                                $record->technicalSupport()->attach($data['user_id']);
                                $ticketHistory = new TicketHistory([
                                    'ticket_id' => $record->id,
                                    'title' => 'SL1: ' . User::where('id', $data['user_id'])->first()->email . ' added to this ticket',
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
                                ->title('SL1 added to this ticket')
                                ->success()
                                ->send();
                        } catch (Exception) {
                            Notification::make()
                                ->title('SL1 already add to this ticket')
                                ->warning()
                                ->send();
                        }
                    }),
                Actions\Action::make('remove_technical_support')
                    ->label('Remove SL1')
                    ->hidden(!(auth()->user()->can('remove_technical_support_ticket')))
                    ->visible(function (Ticket $record) {
                        if (TicketResource::isTicketEnabled($record)) {
                            return $record->technicalSupport->count() >= 2;
                        } else {
                            return false;
                        }
                    })
                    ->requiresConfirmation()
                    ->form([
                        Select::make('user_id')
                            ->label('SL1 User')
                            ->options(function ($record) {
                                return $record->technicalSupport
                                    ->pluck('email', 'id');
                            })
                            ->required(),
                    ])
                    ->action(function (array $data, Ticket $record): void {
                        DB::transaction(function () use ($data, $record) {
                            $record->technicalSupport()->detach($data['user_id']);
                            $ticketHistory = new TicketHistory([
                                'ticket_id' => $record->id,
                                'title' => 'SL1: ' . User::where('id', $data['user_id'])->first()->email . ' removed from this ticket',
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
                            ->title('SL1 removed from this ticket')
                            ->success()
                            ->send();
                    }),
                Actions\Action::make('add_high_technical_support')
                    ->label('Add SL2')
                    ->hidden(!(auth()->user()->can('add_high_technical_support_ticket')))
                    ->visible(function (Ticket $record) {
                        if (TicketResource::isTicketEnabled($record)) {
                            return $record->highTechnicalSupport->count() >= 1;
                        } else {
                            return false;
                        }
                    })
                    ->requiresConfirmation()
                    ->form([
                        Select::make('user_id')
                            ->label('SL2 User')
                            ->options(function ($record) {
                                return Self::getHighTechnicalSupportUsers($record);
                            })
                            ->required(),
                    ])
                    ->action(function (array $data, Ticket $record): void {
                        try {
                            DB::transaction(function () use ($data, $record) {
                                $record->highTechnicalSupport()->attach($data['user_id']);
                                $ticketHistory = new TicketHistory([
                                    'ticket_id' => $record->id,
                                    'title' => 'SL2: ' . User::where('id', $data['user_id'])->first()->email . ' added to this ticket',
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
                                ->title('SL2 added to this ticket')
                                ->success()
                                ->send();
                        } catch (Exception) {
                            Notification::make()
                                ->title('SL2 already add to this ticket')
                                ->warning()
                                ->send();
                        }
                    }),
                Actions\Action::make('remove_high_technical_support')
                    ->label('Remove SL2')
                    ->hidden(!(auth()->user()->can('remove_high_technical_support_ticket')))
                    ->visible(function (Ticket $record) {
                        if (TicketResource::isTicketEnabled($record)) {
                            return $record->highTechnicalSupport->count() >= 2;
                        } else {
                            return false;
                        }
                    })
                    ->requiresConfirmation()
                    ->form([
                        Select::make('user_id')
                            ->label('SL2 User')
                            ->options(function ($record) {
                                return $record->highTechnicalSupport
                                    ->pluck('email', 'id');
                            })
                            ->required(),
                    ])
                    ->action(function (array $data, Ticket $record): void {
                        DB::transaction(function () use ($data, $record) {
                            $record->highTechnicalSupport()->detach($data['user_id']);
                            $ticketHistory = new TicketHistory([
                                'ticket_id' => $record->id,
                                'title' => 'SL2: ' . User::where('id', $data['user_id'])->first()->email . ' removed to this ticket',
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
                            ->title('SL2 removed from this ticket')
                            ->success()
                            ->send();
                    }),
            ])
                ->label('Manage Users')
                ->icon('heroicon-m-ellipsis-vertical')
                ->size(ActionSize::Small)
                ->color('primary')
                ->button(),
        ];
    }

    private static function getTechnicalSupportUsers($record): Collection
    {
        $users = User::where('department_id', $record->department_id)
            ->where('level_id', '=', 2)
            ->pluck('email', 'id');
        $managers = User::permission('can_be_assigned_as_technical_support_ticket')
            ->pluck('email', 'id');
        return $users->union($managers);
    }

    private static function getHighTechnicalSupportUsers($record): Collection
    {
        $users = User::where('department_id', $record->department_id)
            ->where('level_id', '=', 3)
            ->pluck('email', 'id');
        $managers = User::permission('can_be_assigned_as_high_technical_support_ticket')
            ->pluck('email', 'id');
        return $users->union($managers);
    }

    private static function getCreateOrderTypeForm(): array
    {
        return [
            Section::make()
                ->schema([
                    FileUpload::make('attachments')
                        ->getUploadedFileNameForStorageUsing(
                            fn (TemporaryUploadedFile $file, $get): string => (string) str($file->getClientOriginalName())
                                ->prepend(now()->toDateString() . '-' . $get('work_order') . '-'),
                        )
                        ->multiple()
                        ->columnSpanFull(),
                    Section::make('Choose Work Order Type')
                        ->schema([
                            Select::make('work_order')
                                ->required()
                                ->live()
                                ->afterStateUpdated(function ($record, $state, $set) {
                                    if (is_null($state)) {
                                        $set('title', null);
                                        $set('body', null);
                                        $set('email_title', null);
                                        $set('email_body', null);
                                        // $set('from', null);
                                        $set('cc', null);
                                        $set('to', null);
                                    }
                                    if (
                                        $state == TicketWorkOrder::FEEDBACK_TO_CUSTOMER->value || $state == TicketWorkOrder::FEEDBACK_TO_TECHNICAL_SUPPORT->value
                                    ) {
                                        $set('title', null);
                                        $set('body', null);
                                        $set('email_title', null);
                                        $set('email_body', null);
                                        // $set('from', null);
                                        $set('cc', null);
                                        $set('to', null);
                                    } else {
                                        $set('title', $state . ' - ' . ' case ' . ' # ' . $record->ticket_identifier . ' - ' . $record->title);
                                        $set('body', null);
                                        $set('email_title', $state . ' - ' . ' case ' . ' # ' . $record->ticket_identifier . ' - ' . $record->title);
                                        $set('email_body', null);
                                        // $set('from', auth()->user()->email);
                                        $set('cc', null);
                                        $set('to', null);
                                    }
                                })
                                ->options(function ($record) {
                                    return Self::getTicketOrderType($record);
                                }),
                            Select::make('sub_work_order')
                                ->live()
                                ->afterStateUpdated(function ($record, $state, $set) {
                                    if (is_null($state)) {
                                        $set('title', null);
                                        $set('body', null);
                                        $set('email_title', null);
                                        $set('email_body', null);
                                        // $set('from', null);
                                        $set('cc', null);
                                        $set('to', null);
                                    } else {
                                        $set('title', $state . ' - ' . ' case ' . ' # ' . $record->ticket_identifier . ' - ' . $record->title);
                                        $set('body', null);
                                        $set('email_title', $state . ' - ' . ' case ' . ' # ' . $record->ticket_identifier . ' - ' . $record->title);
                                        $set('email_body', null);
                                        // $set('from', auth()->user()->email);
                                        $set('cc', null);
                                        $set('to', null);
                                    }
                                })
                                ->required(function ($get) {
                                    return match ($get('work_order')) {
                                        TicketWorkOrder::FEEDBACK_TO_CUSTOMER->value => true,
                                        TicketWorkOrder::FEEDBACK_TO_TECHNICAL_SUPPORT->value => true,
                                        default => false,
                                    };
                                })
                                ->visible(function ($get) {
                                    return match ($get('work_order')) {
                                        TicketWorkOrder::FEEDBACK_TO_CUSTOMER->value => true,
                                        TicketWorkOrder::FEEDBACK_TO_TECHNICAL_SUPPORT->value => true,
                                        default => false,
                                    };
                                })
                                ->options(function ($get) {
                                    return match ($get('work_order')) {
                                        TicketWorkOrder::FEEDBACK_TO_CUSTOMER->value => [
                                            TicketSubWorkOrder::CUSTOMER_INFORMATION_REQUIRED->value => 'Customer Information Required',
                                            TicketSubWorkOrder::WORKAROUND_CUSTOMER_INFORMATION->value => 'Workaround Customer Information',
                                            TicketSubWorkOrder::FINAL_CUSTOMER_INFORMATION->value => 'Final Customer Information',
                                        ],
                                        TicketWorkOrder::FEEDBACK_TO_TECHNICAL_SUPPORT->value => [
                                            TicketSubWorkOrder::TECHNICAL_SUPPORT_INFORMATION_REQUIRED->value => 'SL1 Information Required',
                                            TicketSubWorkOrder::WORKAROUND_TECHNICAL_SUPPORT_INFORMATION->value => 'Workaround SL1 Information',
                                            TicketSubWorkOrder::FINAL_TECHNICAL_SUPPORT_INFORMATION->value => 'Final SL1 Information',
                                        ],
                                        default => [],
                                    };
                                }),
                        ]),
                    Section::make('Work Order Type Info')
                        ->schema([
                            TextInput::make('title')
                                ->disabled(true)
                                ->dehydrated(true)
                                ->required()
                                ->maxLength(255),
                            Textarea::make('body')
                                ->required()
                                ->maxLength(255),
                            Checkbox::make('send_email')
                                ->hidden(!(auth()->user()->can('send_email_in_order_type_ticket')))
                                ->live(),
                        ])
                        ->columnSpan(1)
                        ->columns(1),
                    Section::make('Work Order Type Email')
                        ->visible(function ($get) {
                            return Self::orderTypeEmailFormCondition($get);
                        })
                        ->schema([
                            TextInput::make('email_title')
                                ->disabled(true)
                                ->dehydrated(true)
                                ->required(function ($get) {
                                    return Self::orderTypeEmailFormCondition($get);
                                }),
                            Textarea::make('email_body')
                                ->required(function ($get) {
                                    return Self::orderTypeEmailFormCondition($get);
                                }),
                            TextInput::make('from')
                                ->disabled(true)
                                ->dehydrated(true)
                                ->default('noreply@vibrafone.co')
                                ->email()
                                ->required(function ($get) {
                                    return Self::orderTypeEmailFormCondition($get);
                                }),
                            TextInput::make('to')
                                ->email()
                                ->required(function ($get) {
                                    return Self::orderTypeEmailFormCondition($get);
                                }),
                            TextInput::make('cc')
                                ->rules([new Emails])
                                ->prefix(auth()->user()->email . ',')
                                ->required(function ($get) {
                                    return Self::orderTypeEmailFormCondition($get);
                                }),
                        ])
                        ->columnSpan(1)
                        ->columns(1),
                ])
                ->columns(2),
        ];
    }

    private static function orderTypeEmailFormCondition($get): bool
    {
        return $get('send_email');
    }

    private static function getTicketOrderType($record): array
    {
        if (auth()->user()->can('view_all_create_order_type_ticket')) {
            if ($record->level_id == Level::where('code', 2)->first()->id) {
                return [
                    'SL1' => [
                        TicketWorkOrder::FEEDBACK_TO_TECHNICAL_SUPPORT->value => 'Feedback to SL1',
                        TicketWorkOrder::TECHNICAL_SUPPORT_TROUBLESHOOTING_ACTIVITY->value => 'Troubleshooting Activity',
                        TicketWorkOrder::TECHNICAL_SUPPORT_RESPONSE->value => 'SL1 Response',
                        TicketWorkOrder::WORKAROUND_ACCEPTED_BY_TECHNICAL_SUPPORT->value => 'Workaround Accepted by SL1',
                        TicketWorkOrder::RESOLUTION_ACCEPTED_BY_TECHNICAL_SUPPORT->value => 'Resolution Accepted by SL1',
                    ],
                    'Customer' => [
                        TicketWorkOrder::FEEDBACK_TO_CUSTOMER->value => 'Feedback to Customer',
                        TicketWorkOrder::CUSTOMER_TROUBLESHOOTING_ACTIVITY->value => 'Troubleshooting Activity',
                        TicketWorkOrder::CUSTOMER_RESPONSE->value => 'Customer Response',
                        TicketWorkOrder::WORKAROUND_ACCEPTED_BY_CUSTOMER->value => 'Workaround Accepted By Customer',
                        TicketWorkOrder::RESOLUTION_ACCEPTED_BY_CUSTOMER->value => 'Resolution Accepted by Customer',
                    ],
                ];
            } else {
                return [
                    'Customer' => [
                        TicketWorkOrder::FEEDBACK_TO_CUSTOMER->value => 'Feedback to Customer',
                        TicketWorkOrder::CUSTOMER_TROUBLESHOOTING_ACTIVITY->value => 'Troubleshooting Activity',
                        TicketWorkOrder::CUSTOMER_RESPONSE->value => 'Customer Response',
                        TicketWorkOrder::WORKAROUND_ACCEPTED_BY_CUSTOMER->value => 'Workaround Accepted By Customer',
                        TicketWorkOrder::RESOLUTION_ACCEPTED_BY_CUSTOMER->value => 'Resolution Accepted by Customer',
                    ],
                ];
            }
            return [];
        }
        if (auth()->user()->can('view_customer_create_order_type_ticket')) {
            return [
                'Customer' => [
                    TicketWorkOrder::CUSTOMER_RESPONSE->value => 'Customer Response',
                    TicketWorkOrder::WORKAROUND_ACCEPTED_BY_CUSTOMER->value => 'Workaround Accepted By Customer',
                    TicketWorkOrder::RESOLUTION_ACCEPTED_BY_CUSTOMER->value => 'Resolution Accepted by Customer',
                ],
            ];
        }
        if ($record->level_id == Level::where('code', 2)->first()->id && auth()->user()->can('view_high_technical_support_create_order_type_ticket')) {
            return [
                'SL1' => [
                    TicketWorkOrder::FEEDBACK_TO_TECHNICAL_SUPPORT->value => 'Feedback to SL1',
                    TicketWorkOrder::TECHNICAL_SUPPORT_TROUBLESHOOTING_ACTIVITY->value => 'Troubleshooting Activity',
                ],
            ];
        }
        return [];
    }

    private static function createOrderType($data, $record): void
    {
        DB::transaction(function () use ($data, $record) {
            $redirectFlag = false;
            if ($data['work_order'] == TicketWorkOrder::FEEDBACK_TO_CUSTOMER->value) {
                if ($data['sub_work_order'] == TicketSubWorkOrder::CUSTOMER_INFORMATION_REQUIRED->value) {
                    $record->status = TicketStatus::CUSTOMER_PENDING->value;
                    $record->handler = TicketHandler::CUSTOMER->value;
                    $status = TicketStatus::CUSTOMER_PENDING->value;
                    $handler = TicketHandler::CUSTOMER->value;
                    $record->work_order = $data['work_order'];
                    $record->sub_work_order = $data['sub_work_order'];
                }
                if ($data['sub_work_order'] == TicketSubWorkOrder::WORKAROUND_CUSTOMER_INFORMATION->value) {
                    $record->status = TicketStatus::CUSTOMER_UNDER_MONITORING->value;
                    $record->handler = TicketHandler::CUSTOMER->value;
                    $status = TicketStatus::CUSTOMER_UNDER_MONITORING->value;
                    $handler = TicketHandler::CUSTOMER->value;
                    $record->work_order = $data['work_order'];
                    $record->sub_work_order = $data['sub_work_order'];
                }
                if ($data['sub_work_order'] == TicketSubWorkOrder::FINAL_CUSTOMER_INFORMATION->value) {
                    $record->status = TicketStatus::CUSTOMER_UNDER_MONITORING->value;
                    $record->handler = TicketHandler::CUSTOMER->value;
                    $status = TicketStatus::CUSTOMER_UNDER_MONITORING->value;
                    $handler = TicketHandler::CUSTOMER->value;
                    $record->work_order = $data['work_order'];
                    $record->sub_work_order = $data['sub_work_order'];
                }
            }
            if ($data['work_order'] == TicketWorkOrder::CUSTOMER_TROUBLESHOOTING_ACTIVITY->value) {
                $record->status = TicketStatus::IN_PROGRESS->value;
                $record->handler = TicketHandler::TECHNICAL_SUPPORT->value;
                $status = TicketStatus::IN_PROGRESS->value;
                $handler = TicketHandler::TECHNICAL_SUPPORT->value;
                $record->work_order = $data['work_order'];
                $record->sub_work_order = null;
            }
            if ($data['work_order'] == TicketWorkOrder::CUSTOMER_RESPONSE->value) {
                $record->status = TicketStatus::IN_PROGRESS->value;
                $record->handler = TicketHandler::TECHNICAL_SUPPORT->value;
                $status = TicketStatus::IN_PROGRESS->value;
                $handler = TicketHandler::TECHNICAL_SUPPORT->value;
                $record->work_order = $data['work_order'];
                $record->sub_work_order = null;
            }
            if ($data['work_order'] == TicketWorkOrder::WORKAROUND_ACCEPTED_BY_CUSTOMER->value) {
                $record->work_order = $data['work_order'];
                $record->sub_work_order = null;
                $status = $record->status;
                $handler = $record->handler;
            }
            if ($data['work_order'] == TicketWorkOrder::RESOLUTION_ACCEPTED_BY_CUSTOMER->value) {
                $record->status = TicketStatus::CLOSED->value;
                $record->handler = TicketHandler::CUSTOMER->value;
                $status = TicketStatus::CLOSED->value;
                $handler = TicketHandler::CUSTOMER->value;
                $record->work_order = $data['work_order'];
                $record->sub_work_order = null;
                $record->end_at = now();
                $redirectFlag = true;
            }
            if ($data['work_order'] == TicketWorkOrder::FEEDBACK_TO_TECHNICAL_SUPPORT->value) {
                if ($data['sub_work_order'] == TicketSubWorkOrder::TECHNICAL_SUPPORT_INFORMATION_REQUIRED->value) {
                    $record->status = TicketStatus::TECHNICAL_SUPPORT_PENDING->value;
                    $record->handler = TicketHandler::TECHNICAL_SUPPORT->value;
                    $status = TicketStatus::TECHNICAL_SUPPORT_PENDING->value;
                    $handler = TicketHandler::TECHNICAL_SUPPORT->value;
                    $record->work_order = $data['work_order'];
                    $record->sub_work_order = $data['sub_work_order'];
                }
                if ($data['sub_work_order'] == TicketSubWorkOrder::WORKAROUND_TECHNICAL_SUPPORT_INFORMATION->value) {
                    $record->status = TicketStatus::TECHNICAL_SUPPORT_UNDER_MONITORING->value;
                    $record->handler = TicketHandler::TECHNICAL_SUPPORT->value;
                    $status = TicketStatus::TECHNICAL_SUPPORT_UNDER_MONITORING->value;
                    $handler = TicketHandler::TECHNICAL_SUPPORT->value;
                    $record->work_order = $data['work_order'];
                    $record->sub_work_order = $data['sub_work_order'];
                }
                if ($data['sub_work_order'] == TicketSubWorkOrder::FINAL_TECHNICAL_SUPPORT_INFORMATION->value) {
                    $record->status = TicketStatus::TECHNICAL_SUPPORT_UNDER_MONITORING->value;
                    $record->handler = TicketHandler::TECHNICAL_SUPPORT->value;
                    $status = TicketStatus::TECHNICAL_SUPPORT_UNDER_MONITORING->value;
                    $handler = TicketHandler::TECHNICAL_SUPPORT->value;
                    $record->work_order = $data['work_order'];
                    $record->sub_work_order = $data['sub_work_order'];
                }
            }
            if ($data['work_order'] == TicketWorkOrder::TECHNICAL_SUPPORT_TROUBLESHOOTING_ACTIVITY->value) {
                $record->status = TicketStatus::HIGH_TECHNICAL_SUPPORT_PENDING->value;
                $record->handler = TicketHandler::HIGH_TECHNICAL_SUPPORT->value;
                $status = TicketStatus::HIGH_TECHNICAL_SUPPORT_PENDING->value;
                $handler = TicketHandler::HIGH_TECHNICAL_SUPPORT->value;
                $record->work_order = $data['work_order'];
                $record->sub_work_order = null;
            }
            if ($data['work_order'] == TicketWorkOrder::TECHNICAL_SUPPORT_RESPONSE->value) {
                $record->status = TicketStatus::HIGH_TECHNICAL_SUPPORT_PENDING->value;
                $record->handler = TicketHandler::HIGH_TECHNICAL_SUPPORT->value;
                $status = TicketStatus::HIGH_TECHNICAL_SUPPORT_PENDING->value;
                $handler = TicketHandler::HIGH_TECHNICAL_SUPPORT->value;
                $record->work_order = $data['work_order'];
                $record->sub_work_order = null;
            }
            if ($data['work_order'] == TicketWorkOrder::WORKAROUND_ACCEPTED_BY_TECHNICAL_SUPPORT->value) {
                $record->status = TicketStatus::TECHNICAL_SUPPORT_PENDING->value;
                $record->handler = TicketHandler::TECHNICAL_SUPPORT->value;
                $status = TicketStatus::TECHNICAL_SUPPORT_PENDING->value;
                $handler = TicketHandler::TECHNICAL_SUPPORT->value;
                $record->work_order = $data['work_order'];
                $record->sub_work_order = null;
            }
            if ($data['work_order'] == TicketWorkOrder::RESOLUTION_ACCEPTED_BY_TECHNICAL_SUPPORT->value) {
                $record->status = TicketStatus::TECHNICAL_SUPPORT_PENDING->value;
                $record->handler = TicketHandler::TECHNICAL_SUPPORT->value;
                $status = TicketStatus::TECHNICAL_SUPPORT_PENDING->value;
                $handler = TicketHandler::TECHNICAL_SUPPORT->value;
                $record->work_order = $data['work_order'];
                $record->sub_work_order = null;
            }
            if (isset($data['send_email'])) {
                if ($data['send_email']) {
                    $data['cc'] = $data['cc'] . ',' . auth()->user()->email;
                    Mail::to($data['to'])->send(new TicketWorkOrderMail($data, $data['attachments']));
                }
            }
            $ticketHistory = new TicketHistory([
                'ticket_id' => $record->id,
                'title' => $data['title'],
                'body' => $data['body'] ?? null,
                'owner' => auth()->user()->email,
                'work_order' => $data['work_order'],
                'sub_work_order' => $data['sub_work_order'] ?? null,
                'attachments' => $data['attachments'],
                'status' => $status,
                'handler' => $handler,
                'created_at' => now(),
            ]);
            $record->ticketHistory()->save($ticketHistory);
            $record->save();
            if ($redirectFlag) {
                Notification::make()
                    ->title('Ticket has been closed')
                    ->success()
                    ->send();
                return redirect('/admin/tickets/' . $record->id);
            } else {
                Notification::make()
                    ->title('Work order created successfully')
                    ->success()
                    ->send();
            }
        });
    }
}
