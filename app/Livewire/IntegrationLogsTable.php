<?php

namespace App\Livewire;

use App\Models\IntegrationLog;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

class IntegrationLogsTable extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => IntegrationLog::query()->with('order'))
            ->columns([
                TextColumn::make('created_at')
                    ->label('Time')
                    ->dateTime('M j, H:i:s')
                    ->sortable(),
                TextColumn::make('direction')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'inbound' ? 'info' : 'warning')
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                TextColumn::make('channel')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('event')
                    ->searchable()
                    ->fontFamily('mono'),
                IconColumn::make('success')
                    ->label('OK')
                    ->boolean(),
                TextColumn::make('http_status')
                    ->label('HTTP')
                    ->badge()
                    ->color(fn (?int $state): string => match (true) {
                        $state === null => 'gray',
                        $state >= 200 && $state < 300 => 'success',
                        $state >= 400 => 'danger',
                        default => 'warning',
                    })
                    ->placeholder('—'),
                TextColumn::make('duration_ms')
                    ->label('ms')
                    ->suffix(' ms')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('reference')
                    ->searchable()
                    ->fontFamily('mono')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('merchant_transaction_id')
                    ->label('Merchant txn')
                    ->searchable()
                    ->fontFamily('mono')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('order.third_party_order_id')
                    ->label('Order')
                    ->url(fn (IntegrationLog $record): ?string => $record->order
                        ? route('orders.show', $record->order)
                        : null)
                    ->color('primary')
                    ->placeholder('—'),
                TextColumn::make('message')
                    ->limit(50)
                    ->wrap()
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('channel')
                    ->options([
                        'vending_api' => 'Vending API',
                        'cellulant_api' => 'Cellulant API',
                        'payment_sync' => 'Payment sync',
                    ]),
                SelectFilter::make('direction')
                    ->options([
                        'inbound' => 'Inbound',
                        'outbound' => 'Outbound',
                    ]),
                SelectFilter::make('success')
                    ->options([
                        '1' => 'Success',
                        '0' => 'Failed',
                    ]),
            ])
            ->recordUrl(fn (IntegrationLog $record): string => route('integration-logs.show', $record))
            ->defaultSort('created_at', 'desc')
            ->searchable()
            ->paginated([10, 25, 50]);
    }

    public function render(): View
    {
        return view('livewire.integration-logs-table');
    }
}
