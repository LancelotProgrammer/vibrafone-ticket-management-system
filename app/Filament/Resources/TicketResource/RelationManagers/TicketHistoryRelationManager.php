<?php

namespace App\Filament\Resources\TicketResource\RelationManagers;

use App\Enums\TicketSubWorkOrder;
use App\Enums\TicketWorkOrder;
use App\Filament\Resources\TicketResource;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
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
                    ->openable()
                    ->disabled()
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $record = $this->getOwnerRecord();
                if (auth()->user()->can('view_history_all_order_type_ticket')) {
                    return $query->where('ticket_id', $record->id)->orderByDesc('created_at', 'des');
                }
                if (auth()->user()->can('view_history_customer_order_type_ticket')) {
                    return $query->where('ticket_id', $record->id)
                        ->orWhere('work_order', TicketWorkOrder::FEEDBACK_TO_CUSTOMER->value)
                        ->orWhere('work_order', TicketWorkOrder::CUSTOMER_TROUBLESHOOTING_ACTIVITY->value)
                        ->orWhere('work_order', TicketWorkOrder::CUSTOMER_RESPONSE->value)
                        ->orWhere('work_order', TicketWorkOrder::WORKAROUND_ACCEPTED_BY_CUSTOMER->value)
                        ->orWhere('work_order', TicketWorkOrder::RESOLUTION_ACCEPTED_BY_CUSTOMER->value)
                        ->orderByDesc('created_at', 'des');
                }
                if (auth()->user()->can('view_history_technical_support_order_type_ticket')) {
                    return $query->where('ticket_id', $record->id)
                        ->orWhere('work_order', TicketWorkOrder::FEEDBACK_TO_CUSTOMER->value)
                        ->orWhere('work_order', TicketWorkOrder::CUSTOMER_TROUBLESHOOTING_ACTIVITY->value)
                        ->orWhere('work_order', TicketWorkOrder::CUSTOMER_RESPONSE->value)
                        ->orWhere('work_order', TicketWorkOrder::WORKAROUND_ACCEPTED_BY_CUSTOMER->value)
                        ->orWhere('work_order', TicketWorkOrder::RESOLUTION_ACCEPTED_BY_CUSTOMER->value)
                        ->orWhere('work_order', TicketWorkOrder::FEEDBACK_TO_TECHNICAL_SUPPORT->value)
                        ->orWhere('work_order', TicketWorkOrder::TECHNICAL_SUPPORT_TROUBLESHOOTING_ACTIVITY->value)
                        ->orWhere('work_order', TicketWorkOrder::TECHNICAL_SUPPORT_RESPONSE->value)
                        ->orWhere('work_order', TicketWorkOrder::WORKAROUND_ACCEPTED_BY_TECHNICAL_SUPPORT->value)
                        ->orWhere('work_order', TicketWorkOrder::RESOLUTION_ACCEPTED_BY_TECHNICAL_SUPPORT->value)
                        ->orderByDesc('created_at', 'des');
                }
                if (auth()->user()->can('view_history_high_technical_support_order_type_ticket')) {
                    return $query->where('ticket_id', $record->id)
                        ->orWhere('work_order', TicketWorkOrder::FEEDBACK_TO_TECHNICAL_SUPPORT->value)
                        ->orWhere('work_order', TicketWorkOrder::TECHNICAL_SUPPORT_TROUBLESHOOTING_ACTIVITY->value)
                        ->orWhere('work_order', TicketWorkOrder::TECHNICAL_SUPPORT_RESPONSE->value)
                        ->orWhere('work_order', TicketWorkOrder::WORKAROUND_ACCEPTED_BY_TECHNICAL_SUPPORT->value)
                        ->orWhere('work_order', TicketWorkOrder::RESOLUTION_ACCEPTED_BY_TECHNICAL_SUPPORT->value)
                        ->orWhere('work_order', TicketWorkOrder::FEEDBACK_TO_HIGH_TECHNICAL_SUPPORT->value)
                        ->orWhere('work_order', TicketWorkOrder::HIGH_TECHNICAL_SUPPORT_TROUBLESHOOTING_ACTIVITY->value)
                        ->orWhere('work_order', TicketWorkOrder::HIGH_TECHNICAL_SUPPORT_RESPONSE->value)
                        ->orWhere('work_order', TicketWorkOrder::WORKAROUND_ACCEPTED_BY_HIGH_TECHNICAL_SUPPORT->value)
                        ->orWhere('work_order', TicketWorkOrder::RESOLUTION_ACCEPTED_BY_HIGH_TECHNICAL_SUPPORT->value)
                        ->orderByDesc('created_at', 'des');
                }
                if (auth()->user()->can('view_history_external_technical_support_order_type_ticket')) {
                    return $query->where('ticket_id', $record->id)
                        ->orWhere('work_order', TicketWorkOrder::FEEDBACK_TO_HIGH_TECHNICAL_SUPPORT->value)
                        ->orWhere('work_order', TicketWorkOrder::HIGH_TECHNICAL_SUPPORT_TROUBLESHOOTING_ACTIVITY->value)
                        ->orWhere('work_order', TicketWorkOrder::HIGH_TECHNICAL_SUPPORT_RESPONSE->value)
                        ->orWhere('work_order', TicketWorkOrder::WORKAROUND_ACCEPTED_BY_HIGH_TECHNICAL_SUPPORT->value)
                        ->orWhere('work_order', TicketWorkOrder::RESOLUTION_ACCEPTED_BY_HIGH_TECHNICAL_SUPPORT->value)
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
                    ->label('View Attachments'),
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
        if (strpos($state, 'Technical Support') !== false) {
            return str_replace('Technical Support', 'SL1', $state);
        } elseif (strpos($state, 'High Technical Support') !== false) {
            return str_replace('High Technical Support', 'SL2', $state);
        } elseif (strpos($state, 'External Technical Support') !== false) {
            return str_replace('External Technical Support', 'SL3', $state);
        } else {
            return $state;
        }
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return auth()->user()->can('view_history_ticket');
    }
}
