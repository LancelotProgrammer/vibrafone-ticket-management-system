<?php

namespace App\Filament\Resources\TicketResource\RelationManagers;

use App\Enums\TicketWorkOrder;
use App\Filament\Resources\TicketResource;
use App\Models\TicketHistory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class TicketHistoryRelationManager extends RelationManager
{
    protected static string $relationship = 'ticketHistory';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DateTimePicker::make('created_at')
                    ->columnSpanFull(),
                Forms\Components\FileUpload::make('attachments')
                    ->dehydrated(false)
                    ->multiple()
                    ->downloadable()
                    ->disabled()
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function () {
                $record = $this->getOwnerRecord();
                $query = TicketHistory::query();
                if (auth()->user()->can('view_history_all_order_type_ticket')) {
                    return $query->where('ticket_id', $record->id)->orderByDesc('created_at', 'des');
                }
                if (auth()->user()->can('view_history_customer_order_type_ticket')) {
                    return $query
                    ->where(function ($query) use ($record) {
                        $query->where('ticket_id', $record->id)
                            ->where('work_order', TicketWorkOrder::FEEDBACK_TO_CUSTOMER->value);
                    })
                    ->orWhere(function ($query) use ($record) {
                        $query->where('ticket_id', $record->id)
                            ->where('work_order', TicketWorkOrder::CUSTOMER_TROUBLESHOOTING_ACTIVITY->value);
                    })
                    ->orWhere(function ($query) use ($record) {
                        $query->where('ticket_id', $record->id)
                            ->where('work_order', TicketWorkOrder::CUSTOMER_RESPONSE->value);
                    })
                    ->orWhere(function ($query) use ($record) {
                        $query->where('ticket_id', $record->id)
                            ->where('work_order', TicketWorkOrder::WORKAROUND_ACCEPTED_BY_CUSTOMER->value);
                    })
                    ->orWhere(function ($query) use ($record) {
                        $query->where('ticket_id', $record->id)
                            ->where('work_order', TicketWorkOrder::RESOLUTION_ACCEPTED_BY_CUSTOMER->value);
                    })
                    ->orderByDesc('created_at', 'des');
                }
                if (auth()->user()->can('view_history_technical_support_order_type_ticket')) {
                    return $query
                    ->where(function ($query) use ($record) {
                        $query->where('ticket_id', $record->id)
                            ->where('work_order', TicketWorkOrder::FEEDBACK_TO_CUSTOMER->value);
                    })
                    ->orWhere(function ($query) use ($record) {
                        $query->where('ticket_id', $record->id)
                            ->where('work_order', TicketWorkOrder::CUSTOMER_TROUBLESHOOTING_ACTIVITY->value);
                    })
                    ->orWhere(function ($query) use ($record) {
                        $query->where('ticket_id', $record->id)
                            ->where('work_order', TicketWorkOrder::CUSTOMER_RESPONSE->value);
                    })
                    ->orWhere(function ($query) use ($record) {
                        $query->where('ticket_id', $record->id)
                            ->where('work_order', TicketWorkOrder::WORKAROUND_ACCEPTED_BY_CUSTOMER->value);
                    })
                    ->orWhere(function ($query) use ($record) {
                        $query->where('ticket_id', $record->id)
                            ->where('work_order', TicketWorkOrder::RESOLUTION_ACCEPTED_BY_CUSTOMER->value);
                    })
                    ->orWhere(function ($query) use ($record) {
                        $query->where('ticket_id', $record->id)
                            ->where('work_order', TicketWorkOrder::FEEDBACK_TO_TECHNICAL_SUPPORT->value);
                    })
                    ->orWhere(function ($query) use ($record) {
                        $query->where('ticket_id', $record->id)
                            ->where('work_order', TicketWorkOrder::TECHNICAL_SUPPORT_TROUBLESHOOTING_ACTIVITY->value);
                    })
                    ->orWhere(function ($query) use ($record) {
                        $query->where('ticket_id', $record->id)
                            ->where('work_order', TicketWorkOrder::TECHNICAL_SUPPORT_RESPONSE->value);
                    })
                    ->orWhere(function ($query) use ($record) {
                        $query->where('ticket_id', $record->id)->where('work_order', TicketWorkOrder::WORKAROUND_ACCEPTED_BY_TECHNICAL_SUPPORT->value);
                    })
                    ->orWhere(function ($query) use ($record) {
                        $query->where('ticket_id', $record->id)->where('work_order', TicketWorkOrder::RESOLUTION_ACCEPTED_BY_TECHNICAL_SUPPORT->value);
                    })
                    ->orderByDesc('created_at', 'des');
                }
                if (auth()->user()->can('view_history_high_technical_support_order_type_ticket')) {
                    return $query
                    ->where(function ($query) use ($record) {
                        $query->where('ticket_id', $record->id)
                            ->where('work_order', TicketWorkOrder::FEEDBACK_TO_TECHNICAL_SUPPORT->value);
                    })
                    ->orWhere(function ($query) use ($record) {
                        $query->where('ticket_id', $record->id)->where('work_order', TicketWorkOrder::TECHNICAL_SUPPORT_TROUBLESHOOTING_ACTIVITY->value);
                    })
                    ->orWhere(function ($query) use ($record) {
                        $query->where('ticket_id', $record->id)->where('work_order', TicketWorkOrder::TECHNICAL_SUPPORT_RESPONSE->value);
                    })
                    ->orWhere(function ($query) use ($record) {
                        $query->where('ticket_id', $record->id)->where('work_order', TicketWorkOrder::WORKAROUND_ACCEPTED_BY_TECHNICAL_SUPPORT->value);
                    })
                    ->orWhere(function ($query) use ($record) {
                        $query->where('ticket_id', $record->id)->where('work_order', TicketWorkOrder::RESOLUTION_ACCEPTED_BY_TECHNICAL_SUPPORT->value);
                    })
                    ->orWhere(function ($query) use ($record) {
                        $query->where('ticket_id', $record->id)->where('work_order', TicketWorkOrder::FEEDBACK_TO_HIGH_TECHNICAL_SUPPORT->value);
                    })
                    ->orWhere(function ($query) use ($record) {
                        $query->where('ticket_id', $record->id)->where('work_order', TicketWorkOrder::HIGH_TECHNICAL_SUPPORT_TROUBLESHOOTING_ACTIVITY->value);
                    })
                    ->orWhere(function ($query) use ($record) {
                        $query->where('ticket_id', $record->id)->where('work_order', TicketWorkOrder::HIGH_TECHNICAL_SUPPORT_RESPONSE->value);
                    })
                    ->orWhere(function ($query) use ($record) {
                        $query->where('ticket_id', $record->id)->where('work_order', TicketWorkOrder::WORKAROUND_ACCEPTED_BY_HIGH_TECHNICAL_SUPPORT->value);
                    })
                    ->orWhere(function ($query) use ($record) {
                        $query->where('ticket_id', $record->id)->where('work_order', TicketWorkOrder::RESOLUTION_ACCEPTED_BY_HIGH_TECHNICAL_SUPPORT->value);
                    })
                    ->orderByDesc('created_at', 'des');
                }
                if (auth()->user()->can('view_history_external_technical_support_order_type_ticket')) {
                    return $query
                    ->where(function ($query) use ($record) {
                        $query->where('ticket_id', $record->id)->where('work_order', TicketWorkOrder::FEEDBACK_TO_HIGH_TECHNICAL_SUPPORT->value);
                    })
                    ->orWhere(function ($query) use ($record) {
                        $query->where('ticket_id', $record->id)->where('work_order', TicketWorkOrder::HIGH_TECHNICAL_SUPPORT_TROUBLESHOOTING_ACTIVITY->value);
                    })
                    ->orWhere(function ($query) use ($record) {
                        $query->where('ticket_id', $record->id)->where('work_order', TicketWorkOrder::HIGH_TECHNICAL_SUPPORT_RESPONSE->value);
                    })
                    ->orWhere(function ($query) use ($record) {
                        $query->where('ticket_id', $record->id)->where('work_order', TicketWorkOrder::WORKAROUND_ACCEPTED_BY_HIGH_TECHNICAL_SUPPORT->value);
                    })
                    ->orWhere(function ($query) use ($record) {
                        $query->where('ticket_id', $record->id)->where('work_order', TicketWorkOrder::RESOLUTION_ACCEPTED_BY_HIGH_TECHNICAL_SUPPORT->value);
                    })
                    ->orderByDesc('created_at', 'des');
                }
            })
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('title')->toggleable()->limit(75)->searchable()
                    ->formatStateUsing(function ($state) {
                        return self::formatTitleUsing($state);
                    }),
                Tables\Columns\TextColumn::make('body')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('work_order')->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(function ($state) {
                        return self::formatTitleUsing($state);
                    }),
                Tables\Columns\TextColumn::make('sub_work_order')->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(function ($state) {
                        return self::formatTitleUsing($state);
                    }),
                Tables\Columns\TextColumn::make('owner')->toggleable(),
                Tables\Columns\TextColumn::make('created_at')->toggleable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->actions([
                EditAction::make()
                    ->action(function ($record, $data) {
                        $record->created_at = $data['created_at'];
                        $record->save();
                        Notification::make()
                            ->title('saved')
                            ->success()
                            ->send();
                    })
                    ->hidden(!(auth()->user()->can('edit_history_date_ticket')))
                    ->visible(function (RelationManager $livewire) {
                        return TicketResource::isTicketEnabled($livewire->getOwnerRecord());
                    }),
                ViewAction::make()
                    ->hidden(!(auth()->user()->can('view_history_attachments_ticket')))
                    ->visible(function ($record) {
                        return !is_null($record->work_order);
                    })
                    ->label('View Attachments'),
                DeleteAction::make()
                    ->hidden(!(auth()->user()->can('delete_history_ticket'))),
            ])
            ->bulkActions([
                //
            ])
            ->emptyStateActions([
                //
            ]);
    }

    public static function formatTitleUsing($state): string
    {
        if (strpos(strtolower($state), 'high technical support') !== false) {
            return str_ireplace('high technical support', 'SL2', $state);
        } elseif (strpos(strtolower($state), 'external technical support') !== false) {
            return str_ireplace('external technical support', 'SL3', $state);
        } elseif (strpos(strtolower($state), 'technical support') !== false) {
            return str_ireplace('technical support', 'SL1', $state);
        } else {
            return $state;
        }
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return auth()->user()->can('view_history_ticket');
    }
}
