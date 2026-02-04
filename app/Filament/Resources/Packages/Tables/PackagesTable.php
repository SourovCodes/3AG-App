<?php

namespace App\Filament\Resources\Packages\Tables;

use App\Filament\Resources\Packages\PackageResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class PackagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn ($record) => $record->product?->type?->getColor() ?? 'gray'),
                TextColumn::make('name')
                    ->label('Package')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn ($record) => $record->slug),
                TextColumn::make('domain_limit')
                    ->label('Domains')
                    ->formatStateUsing(fn ($state) => $state === null ? '∞ Unlimited' : $state)
                    ->badge()
                    ->color(fn ($state) => $state === null ? 'success' : 'info')
                    ->sortable(),
                TextColumn::make('monthly_price')
                    ->label('Monthly')
                    ->money('CHF')
                    ->sortable()
                    ->placeholder('N/A'),
                TextColumn::make('yearly_price')
                    ->label('Yearly')
                    ->money('CHF')
                    ->sortable()
                    ->placeholder('N/A'),
                IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->sortable()
                    ->trueIcon(Heroicon::CheckCircle)
                    ->falseIcon(Heroicon::XCircle),
                TextColumn::make('features')
                    ->label('Features')
                    ->formatStateUsing(fn ($state) => is_array($state) ? count($state).' features' : '0 features')
                    ->badge()
                    ->color('gray')
                    ->toggleable(),
                TextColumn::make('sort_order')
                    ->label('Order')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('product')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->placeholder('All packages')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),
                TernaryFilter::make('domain_limit')
                    ->label('Domain Limit')
                    ->placeholder('All')
                    ->trueLabel('Unlimited only')
                    ->falseLabel('Limited only')
                    ->queries(
                        true: fn ($query) => $query->whereNull('domain_limit'),
                        false: fn ($query) => $query->whereNotNull('domain_limit'),
                    ),
            ])
            ->recordActions([
                ViewAction::make()
                    ->icon(Heroicon::Eye),
                ActionGroup::make([
                    Action::make('activate')
                        ->icon(Heroicon::CheckCircle)
                        ->color('success')
                        ->action(function ($record) {
                            $record->update(['is_active' => true]);
                            Notification::make()->title('Package activated')->success()->send();
                        })
                        ->visible(fn ($record) => ! $record->is_active),
                    Action::make('deactivate')
                        ->icon(Heroicon::XCircle)
                        ->color('warning')
                        ->action(function ($record) {
                            $record->update(['is_active' => false]);
                            Notification::make()->title('Package deactivated')->success()->send();
                        })
                        ->visible(fn ($record) => $record->is_active),
                    EditAction::make()
                        ->icon(Heroicon::Pencil),
                ])->icon(Heroicon::EllipsisVertical),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('activate')
                        ->icon(Heroicon::CheckCircle)
                        ->color('success')
                        ->action(function (Collection $records) {
                            $records->each(fn ($record) => $record->update(['is_active' => true]));
                            Notification::make()->title($records->count().' packages activated')->success()->send();
                        }),
                    BulkAction::make('deactivate')
                        ->icon(Heroicon::XCircle)
                        ->color('warning')
                        ->action(function (Collection $records) {
                            $records->each(fn ($record) => $record->update(['is_active' => false]));
                            Notification::make()->title($records->count().' packages deactivated')->success()->send();
                        }),
                    DeleteBulkAction::make(),
                ]),
            ])
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->striped()
            ->recordUrl(fn ($record): string => PackageResource::getUrl('view', ['record' => $record]));
    }
}
