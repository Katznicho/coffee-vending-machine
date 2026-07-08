<?php

namespace App\Livewire;

use App\Models\CellulantIpnLog;
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

class CellulantIpnLogsTable extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => CellulantIpnLog::query()->with('order'))
            ->columns([
                TextColumn::make('created_at')
                    ->label('Received')
                    ->dateTime('M j, H:i:s')
                    ->sortable(),
                TextColumn::make('status_code')
                    ->label('Payment status')
                    ->state(fn (CellulantIpnLog $record): ?string => $record->paymentStatusCode())
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        '140', '178', '183', '217' => 'success',
                        '180', '216', '99', '101', '102' => 'danger',
                        default => 'gray',
                    })
                    ->placeholder('—'),
                TextColumn::make('request_payload.requestStatusDescription')
                    ->label('Description')
                    ->state(fn (CellulantIpnLog $record): ?string => $record->paymentStatusDescription())
                    ->wrap()
                    ->limit(40)
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('merchant_transaction_id')
                    ->label('Merchant txn')
                    ->searchable()
                    ->fontFamily('mono')
                    ->placeholder('—'),
                TextColumn::make('reference')
                    ->searchable()
                    ->fontFamily('mono')
                    ->placeholder('—'),
                TextColumn::make('msisdn')
                    ->label('Phone')
                    ->toggleable()
                    ->placeholder('—'),
                TextColumn::make('amount')
                    ->formatStateUsing(fn (?int $state): string => $state !== null ? 'UGX '.number_format($state) : '—'),
                IconColumn::make('order_matched')
                    ->label('Matched')
                    ->boolean(),
                TextColumn::make('order.third_party_order_id')
                    ->label('Order')
                    ->url(fn (CellulantIpnLog $record): ?string => $record->order
                        ? route('orders.show', $record->order)
                        : null)
                    ->color('primary')
                    ->placeholder('—'),
                TextColumn::make('ip_address')
                    ->label('IP')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->fontFamily('mono')
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('order_matched')
                    ->label('Order matched')
                    ->options([
                        '1' => 'Matched',
                        '0' => 'Unmatched',
                    ]),
            ])
            ->recordUrl(fn (CellulantIpnLog $record): string => route('ipn-logs.show', $record))
            ->defaultSort('created_at', 'desc')
            ->searchable()
            ->paginated([10, 25, 50]);
    }

    public function render(): View
    {
        return view('livewire.cellulant-ipn-logs-table');
    }
}
