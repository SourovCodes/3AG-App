<?php

namespace App\Filament\Resources\Licenses\Tables;

use App\Enums\LicenseStatus;
use App\Filament\Resources\Licenses\LicenseResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class LicensesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('license_key')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('License key copied!')
                    ->weight('bold')
                    ->limit(20)
                    ->tooltip(fn ($record) => $record->license_key),
                TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->user?->email),
                TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->badge()
                    ->color(fn ($record) => $record->product?->type?->getColor() ?? 'gray'),
                TextColumn::make('package.name')
                    ->label('Package')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('activations_count')
                    ->counts('activeActivations')
                    ->label('Domains')
                    ->formatStateUsing(fn ($state, $record) => $record->domain_limit === null
                        ? "{$state} / ∞"
                        : "{$state} / {$record->domain_limit}")
                    ->badge()
                    ->color(fn ($state, $record) => $record->domain_limit === null
                        ? 'success'
                        : ($state >= $record->domain_limit ? 'danger' : 'info')),
                TextColumn::make('expires_at')
                    ->label('Expires')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Never')
                    ->color(fn ($state) => $state && $state->isPast() ? 'danger' : null),
                TextColumn::make('last_validated_at')
                    ->label('Last Validated')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->placeholder('Never')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(LicenseStatus::class)
                    ->multiple(),
                SelectFilter::make('product')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('package')
                    ->relationship('package', 'name')
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('expires_at')
                    ->label('Expiration')
                    ->placeholder('All')
                    ->trueLabel('Expired')
                    ->falseLabel('Not expired')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('expires_at')->where('expires_at', '<', now()),
                        false: fn ($query) => $query->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', now())),
                    ),
            ])
            ->recordActions([
                ViewAction::make()
                    ->icon(Heroicon::Eye),
                ActionGroup::make([
                    Action::make('suspend')
                        ->icon(Heroicon::PauseCircle)
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            $record->update(['status' => LicenseStatus::Suspended]);
                            Notification::make()->title('License suspended')->success()->send();
                        })
                        ->visible(fn ($record) => $record->status === LicenseStatus::Active),
                    Action::make('activate')
                        ->icon(Heroicon::CheckCircle)
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            $record->update(['status' => LicenseStatus::Active]);
                            Notification::make()->title('License activated')->success()->send();
                        })
                        ->visible(fn ($record) => $record->status !== LicenseStatus::Active),
                    EditAction::make()
                        ->icon(Heroicon::Pencil),
                ])->icon(Heroicon::EllipsisVertical),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('activate')
                        ->icon(Heroicon::CheckCircle)
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $records->each(fn ($record) => $record->update(['status' => LicenseStatus::Active]));
                            Notification::make()->title($records->count().' licenses activated')->success()->send();
                        }),
                    BulkAction::make('suspend')
                        ->icon(Heroicon::PauseCircle)
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $records->each(fn ($record) => $record->update(['status' => LicenseStatus::Suspended]));
                            Notification::make()->title($records->count().' licenses suspended')->success()->send();
                        }),
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->recordUrl(fn ($record): string => LicenseResource::getUrl('view', ['record' => $record]));
    }
}
