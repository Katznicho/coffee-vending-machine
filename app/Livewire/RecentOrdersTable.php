<?php

namespace App\Livewire;

use App\Models\Order;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

class RecentOrdersTable extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Order::query()->latest()->limit(10))
            ->columns([
                TextColumn::make('third_party_order_id')
                    ->label('Reference')
                    ->weight('bold')
                    ->url(fn (Order $record): string => route('orders.show', $record))
                    ->color('primary'),
                TextColumn::make('product_name')
                    ->label('Product'),
                TextColumn::make('amount')
                    ->label('Amount')
                    ->formatStateUsing(fn (int $state): string => 'UGX '.number_format($state)),
                TextColumn::make('payment_status')
                    ->label('Payment')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'pending' => 'warning',
                        'failed' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                TextColumn::make('dispense_status')
                    ->label('Dispense')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'success' => 'success',
                        'failed' => 'danger',
                        default => 'warning',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, H:i'),
            ])
            ->paginated(false);
    }

    public function render(): View
    {
        return view('livewire.recent-orders-table');
    }
}
