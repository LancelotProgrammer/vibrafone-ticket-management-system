<?php

namespace App\Filament\Resources\TicketResource\RelationManagers;

use App\Enums\TicketWorkOrder;
use Filament\Forms;
use Filament\Forms\Form;
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
                    $query->orderByDesc('created_at', 'des');
                }
                if (auth()->user()->can('view_history_customer_order_type_ticket')) {
                    $query->where('work_order', TicketWorkOrder::FEEDBACK_TO_CUSTOMER->value)
                        ->orWhere('work_order', TicketWorkOrder::CUSTOMER_TROUBLESHOOTING_ACTIVITY->value)
                        ->orWhere('work_order', TicketWorkOrder::CUSTOMER_RESPONSE->value)
                        ->orWhere('work_order', TicketWorkOrder::WORKAROUND_ACCEPTED_BY_CUSTOMER->value)
                        ->orWhere('work_order', TicketWorkOrder::RESOLUTION_ACCEPTED_BY_CUSTOMER->value)
                        ->orderByDesc('created_at', 'des');
                }
                if (auth()->user()->can('view_history_support_order_type_ticket')) {
                    $query->where('work_order', TicketWorkOrder::FEEDBACK_TO_TECHNICAL_SUPPORT->value)
                        ->orWhere('work_order', TicketWorkOrder::TECHNICAL_SUPPORT_TROUBLESHOOTING_ACTIVITY->value)
                        ->orWhere('work_order', TicketWorkOrder::TECHNICAL_SUPPORT_RESPONSE->value)
                        ->orWhere('work_order', TicketWorkOrder::RESOLUTION_ACCEPTED_BY_TECHNICAL_SUPPORT->value)
                        ->orderByDesc('created_at', 'des');
                }
            })
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('title')->limit(50),
                Tables\Columns\TextColumn::make('body')->limit(50),
                Tables\Columns\TextColumn::make('work_order'),
                Tables\Columns\TextColumn::make('sub_work_order'),
                Tables\Columns\TextColumn::make('status'),
                Tables\Columns\TextColumn::make('handler'),
                Tables\Columns\TextColumn::make('owner'),
                Tables\Columns\TextColumn::make('created_at'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->actions([
                EditAction::make()
                    ->hidden(!(auth()->user()->can('edit_history_date_ticket'))),
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

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return auth()->user()->can('view_history_ticket');
    }
}
