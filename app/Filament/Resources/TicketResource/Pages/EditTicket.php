<?php

namespace App\Filament\Resources\TicketResource\Pages;

use App\Enums\TicketHandler;
use App\Enums\TicketStatus;
use App\Enums\TicketSubWorkOrder;
use App\Enums\TicketWorkOrder;
use App\Filament\Resources\TicketResource;
use App\Mail\TicketEscalation;
use App\Mail\TicketWorkOrder as TicketWorkOrderMail;
use App\Models\Ticket;
use App\Models\User;
use Filament\Actions;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Mail;

class EditTicket extends EditRecord
{
    protected static string $resource = TicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\Action::make('esclate_ticket')
                ->visible(function (Ticket $record) {
                    if (!is_null($record->deleted_at)) {
                        return false;
                    }
                    if ($record->status == TicketStatus::CLOSED->value) {
                        return false;
                    }
                    return is_null($record->high_technical_support_user_id) && !is_null($record->technical_support_user_id);
                })
                ->form([
                    Select::make('user_id')
                        ->label('High Technical Support User')
                        ->options(User::query()->pluck('name', 'id'))
                        ->required(),
                ])
                ->action(function (array $data, Ticket $record): void {
                    $record->high_technical_support_user_id = $data['user_id'];
                    $record->save();
                    Mail::to(user::find($data['user_id']))->send(new TicketEscalation());
                    $this->refreshFormData([
                        'high_technical_support_user_id',
                    ]);
                    Notification::make()
                        ->title('Ticket has been esclated')
                        ->success()
                        ->send();
                }),
            Actions\Action::make('change_work_order_type')
                ->visible(function (Ticket $record) {
                    if (!is_null($record->deleted_at)) {
                        return false;
                    }
                    if ($record->status == TicketStatus::CLOSED->value) {
                        return false;
                    }
                    return !is_null($record->technical_support_user_id);
                })
                ->form([
                    Section::make()
                        ->schema([
                            Select::make('work_order')
                                ->required()
                                ->live()
                                ->afterStateUpdated(function ($record, $state, $set) {
                                    if (is_null($state)) {
                                        $set('title', null);
                                        $set('email_title', null);
                                        $set('email_body', null);
                                        $set('from', null);
                                        $set('cc', null);
                                        $set('to', null);
                                    }
                                    if ($state == TicketWorkOrder::FEEDBACK_TO_CUSTOMER->value) {
                                        $set('title', null);
                                        $set('email_title', null);
                                    }
                                    if ($state == TicketWorkOrder::CUSTOMER_TROUBLESHOOTING_ACTIVITY->value) {
                                        $set('title', $state . ' - ' . ' case ' . ' # ' . $record->ticket_identifier . ' - ' . $record->title);
                                        $set('email_title', $state . ' - ' . ' case ' . ' # ' . $record->ticket_identifier . ' - ' . $record->title);
                                    }
                                    if ($state == TicketWorkOrder::CUSTOMER_RESPONSE->value) {
                                        $set('title', $state . ' - ' . ' case ' . ' # ' . $record->ticket_identifier . ' - ' . $record->title);
                                        $set('email_title', null);
                                        $set('email_body', null);
                                        $set('from', null);
                                        $set('cc', null);
                                        $set('to', null);
                                    }
                                    if ($state == TicketWorkOrder::RESOLUTION_ACCEPTED_BY_CUSTOMER->value) {
                                        $set('title', $state . ' - ' . ' case ' . ' # ' . $record->ticket_identifier . ' - ' . $record->title);
                                        $set('email_title', null);
                                        $set('email_body', null);
                                        $set('from', null);
                                        $set('cc', null);
                                        $set('to', null);
                                    }
                                    if ($state == TicketWorkOrder::FEEDBACK_TO_TECHNICAL_SUPPORT->value) {
                                        $set('title', null);
                                        $set('email_title', null);
                                    }
                                    if ($state == TicketWorkOrder::TECHNICAL_SUPPORT_TROUBLESHOOTING_ACTIVITY->value) {
                                        $set('title', $state . ' - ' . ' case ' . ' # ' . $record->ticket_identifier . ' - ' . $record->title);
                                        $set('email_title', $state . ' - ' . ' case ' . ' # ' . $record->ticket_identifier . ' - ' . $record->title);
                                    }
                                    if ($state == TicketWorkOrder::TECHNICAL_SUPPORT_RESPONSE->value) {
                                        $set('title', $state . ' - ' . ' case ' . ' # ' . $record->ticket_identifier . ' - ' . $record->title);
                                        $set('email_title', null);
                                        $set('email_body', null);
                                        $set('from', null);
                                        $set('cc', null);
                                        $set('to', null);
                                    }
                                    if ($state == TicketWorkOrder::RESOLUTION_ACCEPTED_BY_TECHNICAL_SUPPORT->value) {
                                        $set('title', $state . ' - ' . ' case ' . ' # ' . $record->ticket_identifier . ' - ' . $record->title);
                                        $set('email_title', null);
                                        $set('email_body', null);
                                        $set('from', null);
                                        $set('cc', null);
                                        $set('to', null);
                                    }
                                })
                                ->options(function ($record) {
                                    if (is_null($record->high_technical_support_user_id)) {
                                        return [
                                            'Customer' => [
                                                TicketWorkOrder::FEEDBACK_TO_CUSTOMER->value => 'Feedback to Customer',
                                                TicketWorkOrder::CUSTOMER_TROUBLESHOOTING_ACTIVITY->value => 'Troubleshooting Activity',
                                                TicketWorkOrder::CUSTOMER_RESPONSE->value => 'Customer Response',
                                                TicketWorkOrder::RESOLUTION_ACCEPTED_BY_CUSTOMER->value => 'Resolution Accepted by Customer',
                                            ],
                                        ];
                                    } else {
                                        return [
                                            'Technical Support' => [
                                                TicketWorkOrder::FEEDBACK_TO_TECHNICAL_SUPPORT->value => 'Feedback to Technical Support',
                                                TicketWorkOrder::TECHNICAL_SUPPORT_TROUBLESHOOTING_ACTIVITY->value => 'Troubleshooting Activity',
                                                TicketWorkOrder::TECHNICAL_SUPPORT_RESPONSE->value => 'Technical Support Response',
                                                TicketWorkOrder::RESOLUTION_ACCEPTED_BY_TECHNICAL_SUPPORT->value => 'Resolution Accepted by Technical Support',
                                            ],
                                            'Customer' => [
                                                TicketWorkOrder::RESOLUTION_ACCEPTED_BY_CUSTOMER->value => 'Resolution Accepted by Customer',
                                            ],
                                        ];
                                    }
                                }),
                            Select::make('sub_work_order')
                                ->live()
                                ->afterStateUpdated(function ($record, $state, $set) {
                                    if (is_null($state)) {
                                        $set('email_title', null);
                                        $set('title', null);
                                    } else {
                                        $set('email_title', $state . ' - ' . ' case ' . ' # ' . $record->ticket_identifier . ' - ' . $record->title);
                                        $set('title', $state . ' - ' . ' case ' . ' # ' . $record->ticket_identifier . ' - ' . $record->title);
                                    }
                                })
                                ->required(function ($get) {
                                    return match ($get('work_order')) {
                                        TicketWorkOrder::FEEDBACK_TO_CUSTOMER->value => true,
                                        TicketWorkOrder::FEEDBACK_TO_TECHNICAL_SUPPORT->value => true,
                                        default => [],
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
                                            TicketSubWorkOrder::TECHNICAL_SUPPORT_INFORMATION_REQUIRED->value => 'Technical Support Information Required',
                                            TicketSubWorkOrder::WORKAROUND_TECHNICAL_SUPPORT_INFORMATION->value => 'Workaround Technical Support Information',
                                            TicketSubWorkOrder::FINAL_CUSTOMER_INFORMATION->value => 'Final Technical Support Information',
                                        ],
                                        default => [],
                                    };
                                }),
                            Section::make('Ticket History Info')
                                ->schema([
                                    TextInput::make('title')
                                        ->disabled(true)
                                        ->dehydrated(true)
                                        ->required()
                                        ->maxLength(255),
                                    Textarea::make('body')
                                        ->required()
                                        ->maxLength(255),
                                ])
                                ->columnSpan(1)
                                ->columns(1),
                            Section::make('Ticket Email')
                                ->disabled(function ($get) {
                                    return match ($get('work_order')) {
                                        TicketWorkOrder::FEEDBACK_TO_CUSTOMER->value => false,
                                        TicketWorkOrder::CUSTOMER_TROUBLESHOOTING_ACTIVITY->value => false,
                                        TicketWorkOrder::FEEDBACK_TO_TECHNICAL_SUPPORT->value => false,
                                        TicketWorkOrder::TECHNICAL_SUPPORT_TROUBLESHOOTING_ACTIVITY->value => false,
                                        default => true,
                                    };
                                })
                                ->schema([
                                    TextInput::make('email_title')
                                        ->disabled(true)
                                        ->dehydrated(true),
                                    Textarea::make('email_body')
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(fn ($set, $state) => $set('body', $state))
                                        ->required(function ($get) {
                                            return match ($get('work_order')) {
                                                TicketWorkOrder::FEEDBACK_TO_CUSTOMER->value => true,
                                                TicketWorkOrder::CUSTOMER_TROUBLESHOOTING_ACTIVITY->value => true,
                                                TicketWorkOrder::FEEDBACK_TO_TECHNICAL_SUPPORT->value => true,
                                                TicketWorkOrder::TECHNICAL_SUPPORT_TROUBLESHOOTING_ACTIVITY->value => true,
                                                default => false,
                                            };
                                        }),
                                    TextInput::make('from')
                                        ->live()
                                        ->email()
                                        ->required(function ($get) {
                                            return match ($get('work_order')) {
                                                TicketWorkOrder::FEEDBACK_TO_CUSTOMER->value => true,
                                                TicketWorkOrder::CUSTOMER_TROUBLESHOOTING_ACTIVITY->value => true,
                                                TicketWorkOrder::FEEDBACK_TO_TECHNICAL_SUPPORT->value => true,
                                                TicketWorkOrder::TECHNICAL_SUPPORT_TROUBLESHOOTING_ACTIVITY->value => true,
                                                default => false,
                                            };
                                        }),
                                    TextInput::make('cc')
                                        ->live()
                                        ->email()
                                        ->required(function ($get) {
                                            return match ($get('work_order')) {
                                                TicketWorkOrder::FEEDBACK_TO_CUSTOMER->value => true,
                                                TicketWorkOrder::CUSTOMER_TROUBLESHOOTING_ACTIVITY->value => true,
                                                TicketWorkOrder::FEEDBACK_TO_TECHNICAL_SUPPORT->value => true,
                                                TicketWorkOrder::TECHNICAL_SUPPORT_TROUBLESHOOTING_ACTIVITY->value => true,
                                                default => false,
                                            };
                                        }),
                                    TextInput::make('to')
                                        ->live()
                                        ->email()
                                        ->required(function ($get) {
                                            return match ($get('work_order')) {
                                                TicketWorkOrder::FEEDBACK_TO_CUSTOMER->value => true,
                                                TicketWorkOrder::CUSTOMER_TROUBLESHOOTING_ACTIVITY->value => true,
                                                TicketWorkOrder::FEEDBACK_TO_TECHNICAL_SUPPORT->value => true,
                                                TicketWorkOrder::TECHNICAL_SUPPORT_TROUBLESHOOTING_ACTIVITY->value => true,
                                                default => false,
                                            };
                                        }),
                                ])
                                ->columnSpan(1)
                                ->columns(1),
                        ])
                        ->columns(2),
                ])
                ->action(function (array $data, Ticket $record): void {
                    if ($data['work_order'] == TicketWorkOrder::FEEDBACK_TO_CUSTOMER->value) {
                        if ($data['sub_work_order'] == TicketSubWorkOrder::CUSTOMER_INFORMATION_REQUIRED->value) {
                            $record->status = TicketStatus::CUSTOMER_PENDING->value;
                            $record->handler = TicketHandler::CUSTOMER->value;
                            $record->work_order = $data['work_order'];
                            $record->sub_work_order = $data['sub_work_order'];
                            Mail::to($data['to'])->send(new TicketWorkOrderMail($data));
                        }
                        if ($data['sub_work_order'] == TicketSubWorkOrder::WORKAROUND_CUSTOMER_INFORMATION->value) {
                            $record->status = TicketStatus::CUSTOMER_UNDER_MONITORING->value;
                            $record->handler = TicketHandler::CUSTOMER->value;
                            $record->work_order = $data['work_order'];
                            $record->sub_work_order = $data['sub_work_order'];
                            Mail::to($data['to'])->send(new TicketWorkOrderMail($data));
                        }
                        if ($data['sub_work_order'] == TicketSubWorkOrder::FINAL_CUSTOMER_INFORMATION->value) {
                            $record->status = TicketStatus::CUSTOMER_UNDER_MONITORING->value;
                            $record->handler = TicketHandler::CUSTOMER->value;
                            $record->work_order = $data['work_order'];
                            $record->sub_work_order = $data['sub_work_order'];
                            Mail::to($data['to'])->send(new TicketWorkOrderMail($data));
                        }
                    }
                    if ($data['work_order'] == TicketWorkOrder::CUSTOMER_TROUBLESHOOTING_ACTIVITY->value) {
                        $record->status = TicketStatus::IN_PROGRESS->value;
                        $record->handler = TicketHandler::TECHNICAL_SUPPORT->value;
                        $record->work_order = $data['work_order'];
                        $record->sub_work_order = null;
                        Mail::to($data['to'])->send(new TicketWorkOrderMail($data));
                    }
                    if ($data['work_order'] == TicketWorkOrder::CUSTOMER_RESPONSE->value) {
                        $record->status = TicketStatus::IN_PROGRESS->value;
                        $record->handler = TicketHandler::TECHNICAL_SUPPORT->value;
                        $record->work_order = $data['work_order'];
                        $record->sub_work_order = null;
                    }
                    if ($data['work_order'] == TicketWorkOrder::RESOLUTION_ACCEPTED_BY_CUSTOMER->value) {
                        $record->status = TicketStatus::CLOSED->value;
                        $record->handler = TicketHandler::CUSTOMER->value;
                        $record->work_order = $data['work_order'];
                        $record->sub_work_order = null;
                    }
                    if ($data['work_order'] == TicketWorkOrder::FEEDBACK_TO_TECHNICAL_SUPPORT->value) {
                        if ($data['sub_work_order'] == TicketSubWorkOrder::TECHNICAL_SUPPORT_INFORMATION_REQUIRED->value) {
                            $record->status = TicketStatus::TECHNICAL_SUPPORT_PENDING->value;
                            $record->handler = TicketHandler::TECHNICAL_SUPPORT->value;
                            $record->work_order = $data['work_order'];
                            $record->sub_work_order = $data['sub_work_order'];
                            Mail::to($data['to'])->send(new TicketWorkOrderMail($data));
                        }
                        if ($data['sub_work_order'] == TicketSubWorkOrder::WORKAROUND_TECHNICAL_SUPPORT_INFORMATION->value) {
                            $record->status = TicketStatus::TECHNICAL_SUPPORT_UNDER_MONITORING->value;
                            $record->handler = TicketHandler::TECHNICAL_SUPPORT->value;
                            $record->work_order = $data['work_order'];
                            $record->sub_work_order = $data['sub_work_order'];
                            Mail::to($data['to'])->send(new TicketWorkOrderMail($data));
                        }
                        if ($data['sub_work_order'] == TicketSubWorkOrder::FINAL_CUSTOMER_INFORMATION->value) {
                            $record->status = TicketStatus::TECHNICAL_SUPPORT_UNDER_MONITORING->value;
                            $record->handler = TicketHandler::TECHNICAL_SUPPORT->value;
                            $record->work_order = $data['work_order'];
                            $record->sub_work_order = $data['sub_work_order'];
                            Mail::to($data['to'])->send(new TicketWorkOrderMail($data));
                        }
                    }
                    if ($data['work_order'] == TicketWorkOrder::TECHNICAL_SUPPORT_TROUBLESHOOTING_ACTIVITY->value) {
                        $record->status = TicketStatus::HIGHT_LEVEL_SUPPORT_PENDING->value;
                        $record->handler = TicketHandler::TECHNICAL_SUPPORT->value;
                        $record->work_order = $data['work_order'];
                        $record->sub_work_order = null;
                        Mail::to($data['to'])->send(new TicketWorkOrderMail($data));
                    }
                    if ($data['work_order'] == TicketWorkOrder::TECHNICAL_SUPPORT_RESPONSE->value) {
                        $record->status = TicketStatus::HIGHT_LEVEL_SUPPORT_PENDING->value;
                        $record->handler = TicketHandler::HIGH_LEVEL_SUPPORT->value;
                        $record->work_order = $data['work_order'];
                        $record->sub_work_order = null;
                    }
                    if ($data['work_order'] == TicketWorkOrder::RESOLUTION_ACCEPTED_BY_TECHNICAL_SUPPORT->value) {
                        $record->status = TicketStatus::TECHNICAL_SUPPORT_PENDING->value;
                        $record->handler = TicketHandler::TECHNICAL_SUPPORT->value;
                        $record->work_order = $data['work_order'];
                        $record->sub_work_order = null;
                    }
                    // $record->ticketHistory()->associate([
                    //     'title' => $data['title'],
                    //     'body' => $data['body'],
                    //     'work_order' => $data['work_order'],
                    //     'sub_work_order' => $data['sub_work_order'],
                    // ]);
                    $record->save();
                    $this->refreshFormData([
                        'work_order',
                        'sub_work_order',
                        'status',
                        'handler',
                    ]);
                    Notification::make()
                        ->title('Work order changed successfully')
                        ->success()
                        ->send();
                }),
            Actions\Action::make('archive')
                ->requiresConfirmation()
                ->visible(function (Ticket $record) {
                    return is_null($record->deleted_at) && $record->status == TicketStatus::CLOSED->value;
                })
                ->action(function (Ticket $record): void {
                    $record->end_at = now();
                    $record->deleted_at = now();
                    $record->save();
                    $this->refreshFormData([
                        'deleted_at',
                    ]);
                    Notification::make()
                        ->title('Ticket has been archived')
                        ->success()
                        ->send();
                })
        ];
    }
}
