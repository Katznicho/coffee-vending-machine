<?php

namespace App\Livewire;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class UsersTable extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => User::query())
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Email copied'),
                TextColumn::make('is_admin')
                    ->label('Role')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Admin' : 'User')
                    ->color(fn (bool $state): string => $state ? 'primary' : 'gray'),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('is_admin')
                    ->label('Role')
                    ->options([
                        '1' => 'Admin',
                        '0' => 'User',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (($data['value'] ?? null) === null || $data['value'] === '') {
                            return $query;
                        }

                        return $query->where('is_admin', (bool) $data['value']);
                    }),
            ])
            ->recordActions([
                Action::make('edit')
                    ->label('Edit')
                    ->url(fn (User $record): string => route('users.edit', $record))
                    ->icon('heroicon-o-pencil-square'),
                Action::make('delete')
                    ->label('Delete')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Delete user')
                    ->modalDescription(fn (User $record): string => "Are you sure you want to delete {$record->name}? This cannot be undone.")
                    ->modalSubmitActionLabel('Delete')
                    ->visible(fn (User $record): bool => $record->id !== Auth::id())
                    ->action(function (User $record): void {
                        if ($record->id === Auth::id()) {
                            Notification::make()
                                ->title('You cannot delete your own account.')
                                ->danger()
                                ->send();

                            return;
                        }

                        $record->delete();

                        Notification::make()
                            ->title('User deleted')
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('name')
            ->searchable()
            ->paginated([10, 25, 50])
            ->emptyStateHeading('No users yet')
            ->emptyStateDescription('Create a user so they can sign in to the dashboard.')
            ->emptyStateActions([
                Action::make('create')
                    ->label('Add user')
                    ->url(route('users.create'))
                    ->icon('heroicon-o-plus'),
            ]);
    }

    public function render(): View
    {
        return view('livewire.users-table');
    }
}
